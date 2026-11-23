#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <stdint.h>
#include <math.h>

static void fill_rect(uint8_t *img,int w,int h,uint8_t r,uint8_t g,uint8_t b,uint8_t a){
    for(int i=0;i<w*h;i++){int d=i*4;img[d]=r;img[d+1]=g;img[d+2]=b;img[d+3]=a;}
}
static void draw_disc(uint8_t *img,int w,int h,double cx,double cy,double radius,uint8_t r,uint8_t g,uint8_t b,uint8_t a){
    double r2=radius*radius;
    int y0=(int)floor(cy-radius-1); if(y0<0)y0=0;
    int y1=(int)ceil(cy+radius+2); if(y1>h)y1=h;
    int x0=(int)floor(cx-radius-1); if(x0<0)x0=0;
    int x1=(int)ceil(cx+radius+2); if(x1>w)x1=w;
    for(int y=y0;y<y1;y++) for(int x=x0;x<x1;x++){
        double dx=x+0.5-cx, dy=y+0.5-cy;
        if(dx*dx+dy*dy<=r2){int d=(y*w+x)*4; img[d]=r;img[d+1]=g;img[d+2]=b;img[d+3]=a;}
    }
}
static void draw_ring(uint8_t *img,int w,int h,double cx,double cy,double outer_r,double stroke,uint8_t r,uint8_t g,uint8_t b,uint8_t a){
    double inner_r=outer_r-stroke, o2=outer_r*outer_r, i2=inner_r*inner_r;
    int y0=(int)floor(cy-outer_r-1); if(y0<0)y0=0;
    int y1=(int)ceil(cy+outer_r+2); if(y1>h)y1=h;
    int x0=(int)floor(cx-outer_r-1); if(x0<0)x0=0;
    int x1=(int)ceil(cx+outer_r+2); if(x1>w)x1=w;
    for(int y=y0;y<y1;y++) for(int x=x0;x<x1;x++){
        double dx=x+0.5-cx, dy=y+0.5-cy, d2=dx*dx+dy*dy;
        if(d2>=i2 && d2<=o2){int d=(y*w+x)*4; img[d]=r;img[d+1]=g;img[d+2]=b;img[d+3]=a;}
    }
}
static void clip_circle(uint8_t *img,int w,int h,double cx,double cy,double radius){
    double r2=radius*radius;
    for(int y=0;y<h;y++) for(int x=0;x<w;x++){
        double dx=x+0.5-cx, dy=y+0.5-cy;
        if(dx*dx+dy*dy>r2) img[(y*w+x)*4+3]=0;
    }
}
static uint8_t *resize_nn(uint8_t *src,int sw,int sh,int dw,int dh){
    uint8_t *dst=(uint8_t*)malloc((size_t)dw*dh*4);
    for(int y=0;y<dh;y++){
        int sy=(int)(((y+0.5)*sh)/dh); if(sy>=sh) sy=sh-1;
        for(int x=0;x<dw;x++){
            int sx=(int)(((x+0.5)*sw)/dw); if(sx>=sw) sx=sw-1;
            memcpy(dst+(y*dw+x)*4, src+(sy*sw+sx)*4, 4);
        }
    }
    return dst;
}
static uint8_t *crop(uint8_t *src,int sw,int sh,int l,int t,int cw,int ch){
    uint8_t *out=(uint8_t*)calloc((size_t)cw*ch*4,1);
    for(int y=0;y<ch;y++){
        int sy=t+y; if(sy<0||sy>=sh) continue;
        int sx=l; int copy=cw;
        if(sx<0){copy+=sx; sx=0;} if(sx+copy>sw) copy=sw-sx;
        if(copy>0) memcpy(out+y*cw*4 + (l<0?-l:0)*4, src+(sy*sw+sx)*4, (size_t)copy*4);
    }
    return out;
}
static void composite(uint8_t *dst,int dw,int dh,uint8_t *src,int sw,int sh,int ox,int oy){
    for(int y=0;y<sh;y++){
        int dy=oy+y; if(dy<0||dy>=dh) continue;
        for(int x=0;x<sw;x++){
            int dx=ox+x; if(dx<0||dx>=dw) continue;
            int si=(y*sw+x)*4, di=(dy*dw+dx)*4;
            double sa=src[si+3]/255.0; if(sa<=0) continue;
            double da=dst[di+3]/255.0; double oa=sa+da*(1-sa); if(oa<=0) continue;
            for(int c=0;c<3;c++) dst[di+c]=(uint8_t)round((src[si+c]*sa + dst[di+c]*da*(1-sa))/oa);
            dst[di+3]=(uint8_t)round(oa*255);
        }
    }
}
static void write_raw(const char *path,uint8_t *buf,int w,int h){
    FILE *f=fopen(path,"wb"); if(!f){perror(path); exit(1);} fwrite(buf,1,(size_t)w*h*4,f); fclose(f);
}
static uint8_t *read_raw(const char *path,int w,int h){
    FILE *f=fopen(path,"rb"); if(!f){perror(path); exit(1);}
    size_t n=(size_t)w*h*4; uint8_t *buf=(uint8_t*)malloc(n);
    if(fread(buf,1,n,f)!=n){fprintf(stderr,"short read %s\n",path); exit(1);} fclose(f); return buf;
}
int main(int argc,char **argv){
    if(argc<5){fprintf(stderr,"usage: icon_ops master.rgba mw mh outdir\n"); return 1;}
    const char *mpath=argv[1]; int mw=atoi(argv[2]), mh=atoi(argv[3]); const char *out=argv[4];
    uint8_t *master=read_raw(mpath,mw,mh);
    char p[1024];
    int sizes[3]={16,32,48};
    for(int i=0;i<3;i++){
        int s=sizes[i];
        int dim=mw<mh?mw:mh;
        int crop_size=(int)round(dim*0.54);
        int left=(mw-crop_size)/2, top=(mh-crop_size)/2;
        uint8_t *cropped=crop(master,mw,mh,left,top,crop_size,crop_size);
        int inner=(int)round(s*0.76); int pad=(s-inner)/2; int ring=(int)round(s*0.06); if(ring<1) ring=1;
        uint8_t *resized=resize_nn(cropped,crop_size,crop_size,inner,inner);
        clip_circle(resized,inner,inner,inner/2.0,inner/2.0,inner/2.0);
        uint8_t *img=(uint8_t*)calloc((size_t)s*s*4,1);
        draw_disc(img,s,s,s/2.0,s/2.0,s/2.0-ring/2.0,255,255,255,255);
        draw_ring(img,s,s,s/2.0,s/2.0,s/2.0-ring/2.0,ring,62,81,114,255);
        composite(img,s,s,resized,inner,inner,pad,pad);
        clip_circle(img,s,s,s/2.0,s/2.0,s/2.0 - ring/4.0);
        snprintf(p,sizeof(p),"%s/favicon-%dx%d.rgba",out,s,s); write_raw(p,img,s,s);
        free(cropped); free(resized); free(img);
    }
    struct {const char *name; int size; int bg; uint8_t r,g,b,a; double scale;} outs[]={
        {"icon-192.png.rgba",192,0,0,0,0,0,1.0},
        {"icon-512.png.rgba",512,0,0,0,0,0,1.0},
        {"icon-maskable-512.png.rgba",512,1,255,255,255,255,0.70},
        {"apple-touch-icon.png.rgba",180,1,255,255,255,255,0.78},
    };
    for(int i=0;i<4;i++){
        int s=outs[i].size, inner=(int)round(s*outs[i].scale), pad=(s-inner)/2;
        uint8_t *resized=resize_nn(master,mw,mh,inner,inner);
        uint8_t *img=(uint8_t*)calloc((size_t)s*s*4,1);
        if(outs[i].bg) fill_rect(img,s,s,outs[i].r,outs[i].g,outs[i].b,outs[i].a);
        composite(img,s,s,resized,inner,inner,pad,pad);
        snprintf(p,sizeof(p),"%s/%s",out,outs[i].name); write_raw(p,img,s,s);
        free(resized); free(img);
    }
    free(master);
    return 0;
}
