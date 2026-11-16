<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $report['title'] }}</title>
    @include('reports._styles')
</head>
<body>
    <table class="brand" width="100%">
        <tr>
            <td>
                <h1>{{ $report['title'] }}</h1>
                <span class="muted">{{ $organisation }} · {{ $report['subtitle'] ?? '' }}</span>
            </td>
            <td style="text-align: right;">
                <img src="{{ public_path('imgs/youthline-logo-sm.png') }}" alt="" height="42">
            </td>
        </tr>
    </table>

    @if (empty($report['rows']))
        <p class="muted">Nothing to report.</p>
    @else
        <table class="data">
            <thead>
                <tr>
                    @foreach ($report['headings'] as $heading)
                        <th>{{ $heading }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($report['rows'] as $row)
                    <tr>
                        @foreach ($row as $cell)
                            <td class="{{ is_int($cell) || is_float($cell) ? 'num' : '' }}">
                                {{ is_float($cell) ? number_format($cell, 2) : (is_int($cell) ? number_format($cell) : $cell) }}
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">{{ $organisation }} — confidential. Generated {{ now()->format('j F Y, H:i') }}.</div>
</body>
</html>
