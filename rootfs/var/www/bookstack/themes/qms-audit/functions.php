<?php
/**
 * QMS-Audit-Theme für BookStack
 * ------------------------------------------------------------------
 * Freigabe-Workflow (4-Augen-Prinzip) für SOPs:
 *  - Jede neu erstellte oder geänderte Seite erhält automatisch den
 *    Status „In Prüfung".
 *  - Nur Mitglieder der Rolle „Freigeber" (oder Administratoren)
 *    können Seiten freigeben oder zurückweisen – niemals die Person,
 *    die die letzte Änderung selbst vorgenommen hat.
 *  - Alle Vorgänge werden revisionssicher im Freigabe-Protokoll
 *    gespeichert (einsehbar unter /qms/protokoll).
 *
 * Getestet mit BookStack v25.11 (Home Assistant Add-on v4.0.2).
 */

use BookStack\Activity\ActivityType;
use BookStack\Entities\Models\Page;
use BookStack\Facades\Theme;
use BookStack\Theming\ThemeEvents;
use BookStack\Users\Models\Role;
use BookStack\Users\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

const QMS_STATUS_TABLE = 'qms_page_status';
const QMS_LOG_TABLE    = 'qms_audit_log';
const QMS_ROLE_NAME    = 'Freigeber';

/**
 * Einmalige Einrichtung: Tabellen und Rolle „Freigeber" anlegen.
 * Läuft beim ersten Web-Request; eine Markerdatei verhindert
 * unnötige Wiederholungen.
 */
function qms_install(): void
{
    static $checkedThisRequest = false;
    if ($checkedThisRequest) {
        return;
    }
    $checkedThisRequest = true;

    $marker = storage_path('framework/qms_audit_v1.installed');
    if (file_exists($marker)) {
        return;
    }

    try {
        if (!Schema::hasTable(QMS_STATUS_TABLE)) {
            Schema::create(QMS_STATUS_TABLE, function ($table) {
                $table->unsignedBigInteger('page_id')->primary();
                $table->string('status', 32)->default('in_pruefung');
                $table->unsignedBigInteger('changed_by')->nullable();
                $table->string('changed_by_name')->default('');
                $table->timestamp('changed_at')->nullable();
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->string('approved_by_name')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->text('comment')->nullable();
            });
        }

        if (!Schema::hasTable(QMS_LOG_TABLE)) {
            Schema::create(QMS_LOG_TABLE, function ($table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('page_id');
                $table->string('page_name')->default('');
                $table->string('action', 64);
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('user_name')->default('');
                $table->text('comment')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->index('page_id');
            });
        }

        Role::query()->firstOrCreate(
            ['display_name' => QMS_ROLE_NAME],
            ['description' => 'Mitglieder dieser Rolle dürfen SOPs freigeben oder zurückweisen (4-Augen-Prinzip). Diese Rolle wurde vom QMS-Audit-Add-on automatisch angelegt.']
        );

        @file_put_contents($marker, date('c'));
    } catch (\Throwable $e) {
        // Datenbank evtl. noch nicht bereit – beim nächsten Request erneut versuchen.
    }
}

/**
 * Darf diese Person freigeben? (Rolle „Freigeber" oder Admin)
 */
function qms_can_approve(?User $user): bool
{
    if ($user === null) {
        return false;
    }
    try {
        foreach ($user->roles as $role) {
            if ($role->system_name === 'admin' || $role->display_name === QMS_ROLE_NAME) {
                return true;
            }
        }
    } catch (\Throwable $e) {
        return false;
    }
    return false;
}

/**
 * Aktuellen QMS-Status einer Seite holen (oder null).
 */
function qms_status_for(int $pageId): ?object
{
    try {
        return DB::table(QMS_STATUS_TABLE)->where('page_id', $pageId)->first();
    } catch (\Throwable $e) {
        return null;
    }
}

/**
 * Eintrag ins Freigabe-Protokoll schreiben.
 */
function qms_log(int $pageId, string $pageName, string $action, ?User $user, ?string $comment = null): void
{
    try {
        DB::table(QMS_LOG_TABLE)->insert([
            'page_id'    => $pageId,
            'page_name'  => $pageName,
            'action'     => $action,
            'user_id'    => $user->id ?? null,
            'user_name'  => $user->name ?? 'System',
            'comment'    => $comment,
            'created_at' => now(),
        ]);
    } catch (\Throwable $e) {
        // Protokollfehler dürfen BookStack nie blockieren.
    }
}

// ---------------------------------------------------------------------------
// Einrichtung beim ersten Web-Request
// ---------------------------------------------------------------------------
Theme::listen(ThemeEvents::WEB_MIDDLEWARE_BEFORE, function ($request) {
    qms_install();
    return null;
});

// ---------------------------------------------------------------------------
// Seitenänderungen überwachen: neue/geänderte Seiten => „In Prüfung"
// ---------------------------------------------------------------------------
Theme::listen(ThemeEvents::ACTIVITY_LOGGED, function (string $type, $detail) {
    try {
        if (!($detail instanceof Page)) {
            return;
        }

        $map = [
            ActivityType::PAGE_CREATE      => 'Erstellt',
            ActivityType::PAGE_UPDATE      => 'Geändert',
            ActivityType::PAGE_RESTORE     => 'Wiederhergestellt',
            ActivityType::REVISION_RESTORE => 'Ältere Version wiederhergestellt',
        ];

        $user = user();

        if (isset($map[$type])) {
            DB::table(QMS_STATUS_TABLE)->updateOrInsert(
                ['page_id' => $detail->id],
                [
                    'status'           => 'in_pruefung',
                    'changed_by'       => $user->id ?? null,
                    'changed_by_name'  => $user->name ?? '',
                    'changed_at'       => now(),
                    'approved_by'      => null,
                    'approved_by_name' => null,
                    'approved_at'      => null,
                    'comment'          => null,
                ]
            );
            qms_log($detail->id, $detail->name, $map[$type], $user);
        } elseif ($type === ActivityType::PAGE_DELETE) {
            qms_log($detail->id, $detail->name, 'Gelöscht', $user);
            DB::table(QMS_STATUS_TABLE)->where('page_id', $detail->id)->delete();
        }
    } catch (\Throwable $e) {
        try {
            logger()->warning('QMS-Audit-Theme: ' . $e->getMessage());
        } catch (\Throwable $ignored) {
        }
    }
});

// ---------------------------------------------------------------------------
// Eigene Routen: Freigeben, Zurückweisen, Protokoll
// ---------------------------------------------------------------------------
Theme::listen(ThemeEvents::ROUTES_REGISTER_WEB_AUTH, function (Router $router) {

    // SOP freigeben
    $router->post('qms/seite/{id}/freigeben', function (Request $request, int $id) {
        $page = Page::query()->findOrFail($id);
        $user = user();

        if (!qms_can_approve($user)) {
            session()->flash('error', 'Keine Berechtigung: Zum Freigeben ist die Rolle „' . QMS_ROLE_NAME . '“ erforderlich.');
            return redirect($page->getUrl());
        }

        $status     = qms_status_for($page->id);
        $lastEditor = $status->changed_by ?? $page->updated_by;
        if ($lastEditor !== null && (int) $lastEditor === (int) $user->id) {
            session()->flash('error', '4-Augen-Prinzip: Die eigene Änderung kann nicht selbst freigegeben werden. Eine andere Person mit der Rolle „' . QMS_ROLE_NAME . '“ muss prüfen und freigeben.');
            return redirect($page->getUrl());
        }

        $comment = trim((string) $request->input('comment', ''));

        DB::table(QMS_STATUS_TABLE)->updateOrInsert(
            ['page_id' => $page->id],
            [
                'status'           => 'freigegeben',
                'approved_by'      => $user->id,
                'approved_by_name' => $user->name,
                'approved_at'      => now(),
                'comment'          => $comment !== '' ? $comment : null,
            ]
        );

        qms_log($page->id, $page->name, 'Freigegeben', $user, $comment !== '' ? $comment : null);
        session()->flash('success', 'SOP „' . $page->name . '“ wurde freigegeben.');
        return redirect($page->getUrl());
    });

    // SOP zurückweisen (Begründung erforderlich)
    $router->post('qms/seite/{id}/zurueckweisen', function (Request $request, int $id) {
        $page = Page::query()->findOrFail($id);
        $user = user();

        if (!qms_can_approve($user)) {
            session()->flash('error', 'Keine Berechtigung: Zum Zurückweisen ist die Rolle „' . QMS_ROLE_NAME . '“ erforderlich.');
            return redirect($page->getUrl());
        }

        $comment = trim((string) $request->input('comment', ''));
        if ($comment === '') {
            session()->flash('error', 'Bitte eine Begründung für die Zurückweisung angeben.');
            return redirect($page->getUrl());
        }

        DB::table(QMS_STATUS_TABLE)->updateOrInsert(
            ['page_id' => $page->id],
            [
                'status'           => 'zurueckgewiesen',
                'approved_by'      => $user->id,
                'approved_by_name' => $user->name,
                'approved_at'      => now(),
                'comment'          => $comment,
            ]
        );

        qms_log($page->id, $page->name, 'Zurückgewiesen', $user, $comment);
        session()->flash('warning', 'SOP „' . $page->name . '“ wurde zurückgewiesen.');
        return redirect($page->getUrl());
    });

    // Freigabe-Protokoll (für alle angemeldeten Benutzer einsehbar)
    $router->get('qms/protokoll', function () {
        qms_install();
        try {
            $entries = DB::table(QMS_LOG_TABLE)->orderByDesc('id')->limit(300)->get();
        } catch (\Throwable $e) {
            $entries = collect();
        }
        return view('qms.protokoll', ['entries' => $entries]);
    });
});
require_once __DIR__ . '/key_register.php';
