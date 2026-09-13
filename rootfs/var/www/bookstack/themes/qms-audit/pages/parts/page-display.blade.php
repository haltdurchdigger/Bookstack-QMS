{{-- QMS-Audit-Theme: Status-Banner + Freigabe-Aktionen oberhalb des Seiteninhalts --}}
@php
    $qmsShowBanner = false;
    $qmsStatus = null;
    $qmsCanApprove = false;
    $qmsIsOwnEdit = false;
    try {
        $qmsShowBanner = isset($page) && !empty($page->id) && empty($diff) && function_exists('qms_status_for');
        if ($qmsShowBanner) {
            $qmsStatus = qms_status_for($page->id);
            $qmsCanApprove = qms_can_approve(user());
            $qmsIsOwnEdit = $qmsStatus && $qmsStatus->changed_by !== null
                && (int) $qmsStatus->changed_by === (int) user()->id;
        }
    } catch (\Throwable $e) {
        $qmsShowBanner = false;
    }
@endphp

@if($qmsShowBanner)
    @php
        $qmsState = $qmsStatus->status ?? 'kein_status';
        $qmsColors = [
            'in_pruefung'     => ['#fff8e1', '#f9a825', '#6d4c00'],
            'freigegeben'     => ['#e8f5e9', '#2e7d32', '#1b5e20'],
            'zurueckgewiesen' => ['#fdecea', '#c62828', '#8e0000'],
            'kein_status'     => ['#eceff1', '#90a4ae', '#37474f'],
        ];
        [$qmsBg, $qmsBorder, $qmsText] = $qmsColors[$qmsState] ?? $qmsColors['kein_status'];
        $qmsDate = function ($d) {
            return $d ? date('d.m.Y \u\m H:i \U\h\r', strtotime($d)) : '–';
        };
    @endphp
    <div style="background: {{ $qmsBg }}; border: 1px solid {{ $qmsBorder }}; border-left: 6px solid {{ $qmsBorder }}; color: {{ $qmsText }}; border-radius: 4px; padding: 12px 16px; margin-bottom: 20px; font-size: 0.92rem; line-height: 1.5;">

        @if($qmsState === 'freigegeben')
            <strong>✓ Freigegeben</strong> –
            von {{ $qmsStatus->approved_by_name }} am {{ $qmsDate($qmsStatus->approved_at) }}.
            @if(!empty($qmsStatus->comment))
                <br><em>Anmerkung: {{ $qmsStatus->comment }}</em>
            @endif

        @elseif($qmsState === 'in_pruefung')
            <strong>⏳ In Prüfung</strong> – diese SOP ist noch <u>nicht freigegeben</u>.
            <br>Letzte Änderung: {{ $qmsStatus->changed_by_name }} am {{ $qmsDate($qmsStatus->changed_at) }}.

        @elseif($qmsState === 'zurueckgewiesen')
            <strong>✗ Zurückgewiesen</strong> –
            von {{ $qmsStatus->approved_by_name }} am {{ $qmsDate($qmsStatus->approved_at) }}.
            @if(!empty($qmsStatus->comment))
                <br><em>Begründung: {{ $qmsStatus->comment }}</em>
            @endif
            <br>Bitte die SOP überarbeiten – nach dem Speichern wird sie automatisch erneut zur Prüfung vorgelegt.

        @else
            <strong>Kein QMS-Status</strong> – diese Seite wurde zuletzt vor Einführung der
            Freigabefunktion geändert. Ein Freigeber kann den Bestand hiermit freigeben.
        @endif

        @if($qmsCanApprove && $qmsState !== 'freigegeben')
            <div style="margin-top: 10px;">
                @if($qmsIsOwnEdit)
                    <em>4-Augen-Prinzip: Die letzte Änderung stammt von Ihnen –
                    eine andere Person mit der Rolle „Freigeber“ muss diese SOP prüfen und freigeben.</em>
                @else
                    <form action="{{ url('/qms/seite/' . $page->id . '/freigeben') }}" method="POST" style="display: inline;">
                        {{ csrf_field() }}
                        <button type="submit"
                                style="background: #2e7d32; color: #fff; border: 0; border-radius: 4px; padding: 6px 14px; cursor: pointer; font-size: 0.9rem;">
                            ✓ Freigeben
                        </button>
                    </form>
                    <details style="display: inline-block; margin-left: 10px; vertical-align: top;">
                        <summary style="cursor: pointer; color: #c62828; padding: 6px 0;">✗ Zurückweisen …</summary>
                        <form action="{{ url('/qms/seite/' . $page->id . '/zurueckweisen') }}" method="POST" style="margin-top: 6px;">
                            {{ csrf_field() }}
                            <textarea name="comment" rows="2" required
                                      placeholder="Begründung der Zurückweisung (erforderlich)"
                                      style="display: block; width: 100%; min-width: 280px; max-width: 480px; margin-bottom: 6px; padding: 6px; border: 1px solid {{ $qmsBorder }}; border-radius: 4px;"></textarea>
                            <button type="submit"
                                    style="background: #c62828; color: #fff; border: 0; border-radius: 4px; padding: 6px 14px; cursor: pointer; font-size: 0.9rem;">
                                Zurückweisen
                            </button>
                        </form>
                    </details>
                @endif
            </div>
        @endif

        <div style="margin-top: 8px; font-size: 0.8rem;">
            <a href="{{ url('/qms/protokoll') }}" style="color: {{ $qmsText }}; text-decoration: underline;">Freigabe-Protokoll ansehen</a>
        </div>
    </div>
@endif

<div dir="auto">

    <h1 class="break-text" id="bkmrk-page-title">{{$page->name}}</h1>

    <div style="clear:left;"></div>

    @if (isset($diff) && $diff)
        {!! $diff !!}
    @else
        {!! isset($page->renderedHTML) ? $page->renderedHTML : $page->html !!}
    @endif
</div>
