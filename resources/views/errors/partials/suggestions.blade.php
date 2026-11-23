<ul class="pcdr-error__suggestions-list">
    @foreach ($suggestions as $suggestion)
        <li>
            <a href="{{ $suggestion['href'] }}">{{ $suggestion['label'] }}</a>
        </li>
    @endforeach
</ul>
