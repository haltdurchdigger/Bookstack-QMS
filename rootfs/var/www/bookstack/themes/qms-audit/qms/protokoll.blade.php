{{-- QMS-Audit-Theme: Freigabe-Protokoll --}}
@extends('layouts.simple')

@section('body')
    <div class="container small" style="padding-top: 24px; padding-bottom: 48px;">
        <main class="card content-wrap">
            <h1 class="list-heading">Freigabe-Protokoll</h1>
            <p class="text-muted">
                Revisionssicheres Protokoll aller QMS-Vorgänge (Erstellung, Änderung, Freigabe,
                Zurückweisung und Löschung von SOPs). Angezeigt werden die letzten
                {{ count($entries) }} Einträge, neueste zuerst.
            </p>

            @if(count($entries) === 0)
                <p><em>Noch keine Einträge vorhanden.</em></p>
            @else
                <div style="overflow-x: auto;">
                    <table class="table" style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="text-align: left; border-bottom: 2px solid #ccc;">
                                <th style="padding: 6px 10px;">Datum</th>
                                <th style="padding: 6px 10px;">SOP / Seite</th>
                                <th style="padding: 6px 10px;">Aktion</th>
                                <th style="padding: 6px 10px;">Benutzer</th>
                                <th style="padding: 6px 10px;">Kommentar / Begründung</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($entries as $entry)
                                @php
                                    $rowColors = [
                                        'Freigegeben'    => '#2e7d32',
                                        'Zurückgewiesen' => '#c62828',
                                        'Gelöscht'       => '#757575',
                                    ];
                                    $actionColor = $rowColors[$entry->action] ?? '#6d4c00';
                                @endphp
                                <tr style="border-bottom: 1px solid #e0e0e0; vertical-align: top;">
                                    <td style="padding: 6px 10px; white-space: nowrap;">
                                        {{ $entry->created_at ? date('d.m.Y H:i', strtotime($entry->created_at)) : '–' }}
                                    </td>
                                    <td style="padding: 6px 10px;">
                                        <a href="{{ url('/link/' . $entry->page_id) }}">{{ $entry->page_name }}</a>
                                    </td>
                                    <td style="padding: 6px 10px; white-space: nowrap; color: {{ $actionColor }}; font-weight: 600;">
                                        {{ $entry->action }}
                                    </td>
                                    <td style="padding: 6px 10px;">{{ $entry->user_name }}</td>
                                    <td style="padding: 6px 10px;">{{ $entry->comment }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </main>
    </div>
@stop
