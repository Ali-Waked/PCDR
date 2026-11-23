#!/usr/bin/env python3
"""Generate PCDR browser icons from the committed master logo."""
import hashlib
import json
import math
import struct
import zlib
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
PUB = ROOT / "public"
MASTERS = [
    PUB / "assets/pcdr-homepage/logo/pcdr-logo-lg.png",
    PUB / "assets/pcdr-homepage/logo/pcdr-logo.png",
]
NAVY = (0x3E, 0x51, 0x72)
WHITE = (0xFF, 0xFF, 0xFF)


def find_master():
    for path in MASTERS:
        if path.exists():
            return path
    raise SystemExit("Master logo not found")


def paeth(a, b, c):
    p = a + b - c
    pa, pb, pc = abs(p - a), abs(p - b), abs(p - c)
    if pa <= pb and pa <= pc:
        return a
    return b if pb <= pc else c


def png_read(path):
    data = path.read_bytes()
    if data[:8] != b"\x89PNG\r\n\x1a\n":
        raise ValueError("not png")

    index = 8
    width = height = color_type = bit_depth = interlace = None
    idat = b""

    while index < len(data):
        length = struct.unpack(">I", data[index:index + 4])[0]
        chunk_type = data[index + 4:index + 8]
        chunk = data[index + 8:index + 8 + length]
        index += 12 + length

        if chunk_type == b"IHDR":
            width, height, bit_depth, color_type, _, _, interlace = struct.unpack(">IIBBBBB", chunk)
        elif chunk_type == b"IDAT":
            idat += chunk
        elif chunk_type == b"IEND":
            break

    if bit_depth != 8 or interlace != 0:
        raise ValueError("unsupported png")

    channels = {6: 4, 2: 3, 0: 1, 4: 2}.get(color_type)
    if channels is None:
        raise ValueError("unsupported color type")

    raw = zlib.decompress(idat)
    stride = width * channels
    reconstructed = bytearray(height * stride)
    position = 0

    for y in range(height):
        filter_type = raw[position]
        position += 1
        row = raw[position:position + stride]
        position += stride

        for x in range(stride):
            left = reconstructed[y * stride + x - channels] if x >= channels else 0
            above = reconstructed[(y - 1) * stride + x] if y > 0 else 0
            upper_left = reconstructed[(y - 1) * stride + x - channels] if y > 0 and x >= channels else 0
            value = row[x]

            if filter_type == 0:
                reconstructed_value = value
            elif filter_type == 1:
                reconstructed_value = (value + left) & 255
            elif filter_type == 2:
                reconstructed_value = (value + above) & 255
            elif filter_type == 3:
                reconstructed_value = (value + ((left + above) // 2)) & 255
            elif filter_type == 4:
                reconstructed_value = (value + paeth(left, above, upper_left)) & 255
            else:
                raise ValueError("bad filter")

            reconstructed[y * stride + x] = reconstructed_value

    rgba = bytearray(width * height * 4)
    for y in range(height):
        for x in range(width):
            source = y * stride + x * channels
            target = (y * width + x) * 4

            if color_type == 6:
                rgba[target:target + 4] = reconstructed[source:source + 4]
            elif color_type == 2:
                rgba[target:target + 3] = reconstructed[source:source + 3]
                rgba[target + 3] = 255
            elif color_type == 0:
                gray = reconstructed[source]
                rgba[target:target + 3] = bytes([gray, gray, gray])
                rgba[target + 3] = 255
            else:
                gray = reconstructed[source]
                rgba[target:target + 3] = bytes([gray, gray, gray])
                rgba[target + 3] = reconstructed[source + 1]

    return width, height, bytes(rgba)


def png_write(path, width, height, rgba):
    def chunk(chunk_type, payload):
        encoded = struct.pack(">I", len(payload)) + chunk_type + payload
        return encoded + struct.pack(">I", zlib.crc32(chunk_type + payload) & 0xFFFFFFFF)

    raw = bytearray()
    stride = width * 4
    for y in range(height):
        raw.append(0)
        raw.extend(rgba[y * stride:(y + 1) * stride])

    data = (
        b"\x89PNG\r\n\x1a\n"
        + chunk(b"IHDR", struct.pack(">IIBBBBB", width, height, 8, 6, 0, 0, 0))
        + chunk(b"IDAT", zlib.compress(bytes(raw), 9))
        + chunk(b"IEND", b"")
    )
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_bytes(data)


def png_to_ico(entries):
    header = struct.pack("<HHH", 0, 1, len(entries))
    directory = b""
    blobs = b""
    offset = 6 + 16 * len(entries)

    for size, png in entries:
        encoded_size = 0 if size >= 256 else size
        directory += struct.pack("<BBBBHHII", encoded_size, encoded_size, 0, 0, 1, 32, len(png), offset)
        blobs += png
        offset += len(png)

    return header + directory + blobs


def blank(width, height, color=None):
    if color is None:
        return bytearray(width * height * 4)
    return bytearray(b"".join(bytes(color) + b"\xff" for _ in range(width * height)))


def crop(rgba, width, height, left, top, crop_width, crop_height):
    output = bytearray(crop_width * crop_height * 4)
    for y in range(crop_height):
        source_y = top + y
        if not 0 <= source_y < height:
            continue
        start_x = max(0, left)
        end_x = min(width, left + crop_width)
        if end_x <= start_x:
            continue
        count = (end_x - start_x) * 4
        target = (y * crop_width + start_x - left) * 4
        source = (source_y * width + start_x) * 4
        output[target:target + count] = rgba[source:source + count]
    return bytes(output)


def resize(rgba, source_width, source_height, width, height):
    output = bytearray(width * height * 4)
    for y in range(height):
        source_y = max(0.0, min(source_height - 1.0, ((y + 0.5) * source_height / height) - 0.5))
        y0 = math.floor(source_y)
        y1 = min(source_height - 1, y0 + 1)
        weight_y = source_y - y0

        for x in range(width):
            source_x = max(0.0, min(source_width - 1.0, ((x + 0.5) * source_width / width) - 0.5))
            x0 = math.floor(source_x)
            x1 = min(source_width - 1, x0 + 1)
            weight_x = source_x - x0

            target = (y * width + x) * 4
            for channel in range(4):
                top_left = rgba[(y0 * source_width + x0) * 4 + channel]
                top_right = rgba[(y0 * source_width + x1) * 4 + channel]
                bottom_left = rgba[(y1 * source_width + x0) * 4 + channel]
                bottom_right = rgba[(y1 * source_width + x1) * 4 + channel]
                top = top_left * (1 - weight_x) + top_right * weight_x
                bottom = bottom_left * (1 - weight_x) + bottom_right * weight_x
                output[target + channel] = round(top * (1 - weight_y) + bottom * weight_y)

    return bytes(output)


def clip_circle(rgba, size, radius):
    output = bytearray(rgba)
    center = size / 2
    for y in range(size):
        for x in range(size):
            distance = math.hypot(x + 0.5 - center, y + 0.5 - center)
            if distance > radius:
                output[(y * size + x) * 4 + 3] = 0
    return bytes(output)


def draw_disc(canvas, size, radius, color):
    center = size / 2
    for y in range(size):
        for x in range(size):
            distance = math.hypot(x + 0.5 - center, y + 0.5 - center)
            if distance <= radius:
                offset = (y * size + x) * 4
                canvas[offset:offset + 4] = bytes((*color, 255))


def draw_ring(canvas, size, outer_radius, stroke, color):
    center = size / 2
    inner_radius = outer_radius - stroke
    for y in range(size):
        for x in range(size):
            distance = math.hypot(x + 0.5 - center, y + 0.5 - center)
            if inner_radius <= distance <= outer_radius:
                offset = (y * size + x) * 4
                canvas[offset:offset + 4] = bytes((*color, 255))


def composite(canvas, size, overlay, offset_x, offset_y, overlay_size):
    for y in range(overlay_size):
        target_y = offset_y + y
        if not 0 <= target_y < size:
            continue
        for x in range(overlay_size):
            target_x = offset_x + x
            if not 0 <= target_x < size:
                continue

            source_offset = (y * overlay_size + x) * 4
            target_offset = (target_y * size + target_x) * 4
            source_alpha = overlay[source_offset + 3] / 255
            if source_alpha <= 0:
                continue

            target_alpha = canvas[target_offset + 3] / 255
            combined_alpha = source_alpha + target_alpha * (1 - source_alpha)
            if combined_alpha <= 0:
                continue

            for channel in range(3):
                source = overlay[source_offset + channel] * source_alpha
                destination = canvas[target_offset + channel] * target_alpha * (1 - source_alpha)
                canvas[target_offset + channel] = round((source + destination) / combined_alpha)
            canvas[target_offset + 3] = round(combined_alpha * 255)


def emblem(master, width, height, size):
    dimension = min(width, height)
    crop_size = round(dimension * 0.54)
    left = (width - crop_size) // 2
    top = (height - crop_size) // 2
    inner = round(size * 0.76)
    padding = (size - inner) // 2
    ring = max(1, round(size * 0.06))

    cropped = crop(master, width, height, left, top, crop_size, crop_size)
    resized = clip_circle(resize(cropped, crop_size, crop_size, inner, inner), inner, inner / 2)

    canvas = blank(size, size)
    disc_radius = size / 2 - ring / 2
    draw_disc(canvas, size, disc_radius, WHITE)
    draw_ring(canvas, size, disc_radius, ring, NAVY)
    composite(canvas, size, resized, padding, padding, inner)

    return bytes(clip_circle(canvas, size, size / 2 - ring / 4))


def full_icon(master, width, height, size, scale, background=None):
    inner = round(size * scale)
    padding = (size - inner) // 2
    resized = resize(master, width, height, inner, inner)
    canvas = blank(size, size, background)
    composite(canvas, size, resized, padding, padding, inner)
    return bytes(canvas)


def main():
    master_path = find_master()
    master_width, master_height, master = png_read(master_path)
    print("Master:", master_path.relative_to(ROOT))

    favicons = []
    for size in [16, 32, 48]:
        data = emblem(master, master_width, master_height, size)
        path = PUB / f"favicon-{size}x{size}.png"
        png_write(path, size, size, data)
        favicons.append((size, path.read_bytes()))

    (PUB / "favicon.ico").write_bytes(png_to_ico(favicons))

    outputs = {
        "icon-192.png": (192, full_icon(master, master_width, master_height, 192, 1.0)),
        "icon-512.png": (512, full_icon(master, master_width, master_height, 512, 1.0)),
        "icon-maskable-512.png": (512, full_icon(master, master_width, master_height, 512, 0.70, WHITE)),
        "apple-touch-icon.png": (180, full_icon(master, master_width, master_height, 180, 0.78, WHITE)),
    }
    for name, (size, data) in outputs.items():
        png_write(PUB / name, size, size, data)

    manifest = {
        "name": "PCDR",
        "short_name": "PCDR",
        "lang": "ar",
        "dir": "rtl",
        "start_url": "/",
        "display": "standalone",
        "theme_color": "#3E5172",
        "background_color": "#FFFFFF",
        "icons": [
            {"src": "/icon-192.png", "sizes": "192x192", "type": "image/png", "purpose": "any"},
            {"src": "/icon-512.png", "sizes": "512x512", "type": "image/png", "purpose": "any"},
            {"src": "/icon-maskable-512.png", "sizes": "512x512", "type": "image/png", "purpose": "maskable"},
        ],
    }
    (PUB / "site.webmanifest").write_text(json.dumps(manifest, ensure_ascii=False, indent=2) + "\n")

    digest = hashlib.sha256()
    for name in [
        "favicon.ico",
        "favicon-16x16.png",
        "favicon-32x32.png",
        "favicon-48x48.png",
        "icon-192.png",
        "icon-512.png",
        "icon-maskable-512.png",
        "apple-touch-icon.png",
        "site.webmanifest",
    ]:
        digest.update((PUB / name).read_bytes())

    version = digest.hexdigest()[:12]
    (PUB / "icons-version.txt").write_text(version)
    print("Version:", version)


if __name__ == "__main__":
    main()
