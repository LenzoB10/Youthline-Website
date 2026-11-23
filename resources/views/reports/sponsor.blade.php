<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Progress report — {{ $report['sponsor']['name'] }}</title>
    @include('reports._styles')
    <style>
        .student { border: 1px solid #e5e7eb; border-left: 4px solid #1e7d3e; padding: 10px 12px; margin-bottom: 12px; page-break-inside: avoid; }
        .facts td { padding: 2px 10px 2px 0; vertical-align: top; }
        .facts td.label { color: #6b7280; width: 110px; }
        .update { background: #f7f8f4; padding: 8px 10px; margin-top: 8px; font-style: italic; }
        .pill { background: #e8f3ec; color: #1e7d3e; padding: 1px 6px; border-radius: 8px; }
    </style>
</head>
<body>
    <table class="brand" width="100%">
        <tr>
            <td>
                <h1>Progress report</h1>
                <span class="muted">
                    For {{ $report['sponsor']['name'] }}
                    @if ($report['year']) · Academic year {{ $report['year'] }} @endif
                    · {{ \Illuminate\Support\Carbon::parse($report['generated_on'])->format('j F Y') }}
                </span>
            </td>
            <td style="text-align: right;">
                <img src="{{ public_path('imgs/youthline-logo-sm.png') }}" alt="" height="48">
            </td>
        </tr>
    </table>

    <p>
        Thank you for supporting {{ count($report['students']) === 1 ? 'a young person' : count($report['students']).' young people' }}
        through {{ $organisation }}. Here is how {{ count($report['students']) === 1 ? 'they are' : 'each of them is' }} doing.
    </p>

    @forelse ($report['students'] as $student)
        <div class="student">
            <h2>{{ $student['name'] }}</h2>
            <table class="facts">
                <tr>
                    <td class="label">Studying</td>
                    <td>
                        {{ $student['level'] ?? 'Level not recorded' }}@if ($student['school']), {{ $student['school'] }}@endif
                        @if ($student['course']) — {{ $student['course'] }} @endif
                        <span class="pill">{{ $student['status'] }}</span>
                    </td>
                </tr>
                <tr>
                    <td class="label">Sponsored since</td>
                    <td>{{ $student['since'] ? \Illuminate\Support\Carbon::parse($student['since'])->format('F Y') : '—' }}</td>
                </tr>
                <tr>
                    <td class="label">Latest results</td>
                    <td>
                        @forelse ($student['results'] as $result)
                            {{ $result['term'] }}: <strong>{{ $result['score'] }}</strong>@if (! $loop->last) · @endif
                        @empty
                            <span class="muted">No results recorded yet.</span>
                        @endforelse
                    </td>
                </tr>
                <tr>
                    <td class="label">School fees</td>
                    <td>
                        {{ $report['currency'] }} {{ number_format($student['fees']['paid'], 2) }} paid
                        of {{ $report['currency'] }} {{ number_format($student['fees']['share'], 2) }} this year
                    </td>
                </tr>
                <tr>
                    <td class="label">Other support</td>
                    <td>
                        @php
                            $support = array_filter([
                                $student['support']['basic_needs'] > 0 ? $report['currency'].' '.number_format($student['support']['basic_needs'], 2).' for basic needs' : null,
                                $student['support']['sessions'] ? $student['support']['sessions'].' mentorship '.($student['support']['sessions'] === 1 ? 'session' : 'sessions') : null,
                                $student['support']['businesses'] ? 'Business: '.implode(', ', $student['support']['businesses']) : null,
                            ]);
                        @endphp
                        {{ $support ? implode(' · ', $support) : '—' }}
                    </td>
                </tr>
            </table>
            @if ($student['update'])
                <div class="update">“{{ $student['update'] }}”</div>
            @endif
        </div>
    @empty
        <p class="muted">This sponsor has no students linked at the moment.</p>
    @endforelse

    <div class="footer">{{ $organisation }} — prepared for {{ $report['sponsor']['name'] }}. Please keep this report private.</div>
</body>
</html>
