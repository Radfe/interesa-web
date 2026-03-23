<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/inc/functions.php';
require_once dirname(__DIR__) . '/inc/admin-auth.php';
require_once dirname(__DIR__) . '/inc/agent-status.php';

interessa_admin_session_boot();

if (!interessa_admin_is_authenticated()) {
    header('Location: /admin', true, 303);
    exit;
}

$status = interessa_agent_status_for_dashboard();
$page_title = 'AI Status | Interesa';
$page_description = 'Interny AI dashboard pre sledovanie stavu vyvoja projektu.';
$page_canonical = '/admin/ai-status';
$page_robots = 'noindex,nofollow';
$page_styles = [asset('css/admin.css')];

$progressItems = [];
foreach (($status['Overall progress'] ?? []) as $item) {
    if (!preg_match('/^(?<label>.+?)\s+(?<percent>\d{1,3})%$/', (string) $item, $match)) {
        continue;
    }
    $progressItems[] = [
        'label' => trim((string) $match['label']),
        'percent' => max(0, min(100, (int) $match['percent'])),
    ];
}

require dirname(__DIR__) . '/inc/head.php';
?>
<section class="admin-page">
  <div class="container admin-main">
    <article class="admin-card">
      <div class="admin-card-head">
        <div>
          <p class="admin-kicker">AI dashboard</p>
          <h1>Stav vyvoja projektu</h1>
          <p class="admin-note">Prehlad aktualnej vetvy, aktivnej ulohy, dalsich krokov a upravovanych suborov.</p>
        </div>
        <div class="admin-actions">
          <a class="btn btn-outline btn-small" href="/admin">Spat do adminu</a>
        </div>
      </div>
      <div class="admin-grid two-up">
        <div class="admin-subsection">
          <strong>Current branch</strong>
          <p><?= esc((string) ($status['Current branch'] ?? '')) ?></p>
        </div>
        <div class="admin-subsection">
          <strong>Current task</strong>
          <p><?= esc((string) ($status['Current task'] ?? '')) ?></p>
        </div>
        <div class="admin-subsection">
          <strong>Next planned task</strong>
          <p><?= esc((string) ($status['Next planned task'] ?? '')) ?></p>
        </div>
        <div class="admin-subsection">
          <strong>Last completed task</strong>
          <p><?= esc((string) ($status['Last completed task'] ?? '')) ?></p>
        </div>
      </div>
    </article>

    <article class="admin-card">
      <div class="admin-card-head">
        <div>
          <p class="admin-kicker">Modified files</p>
          <h2>Aktivne upravovane subory</h2>
        </div>
      </div>
      <div class="admin-subsection">
        <ul class="admin-queue-list">
          <?php foreach (($status['Files currently modified'] ?? []) as $file): ?>
            <li class="admin-queue-item"><code><?= esc((string) $file) ?></code></li>
          <?php endforeach; ?>
        </ul>
      </div>
    </article>

    <article class="admin-card">
      <div class="admin-card-head">
        <div>
          <p class="admin-kicker">Progress</p>
          <h2>Projektovy progres</h2>
        </div>
      </div>
      <div class="admin-progress-list">
        <?php foreach ($progressItems as $item): ?>
          <div class="admin-progress-item">
            <div class="admin-progress-head">
              <strong><?= esc($item['label']) ?></strong>
              <span><?= esc((string) $item['percent']) ?>%</span>
            </div>
            <div class="admin-progress-bar"><span style="width: <?= esc((string) $item['percent']) ?>%"></span></div>
          </div>
        <?php endforeach; ?>
      </div>
    </article>
  </div>
</section>
<?php require dirname(__DIR__) . '/inc/footer.php'; ?>
