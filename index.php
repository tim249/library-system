<?php
require_once __DIR__ . '/../Includes/Auth_check.php';
require_role('admin', 'librarian');
$counts = (new Loan(db()))->dashboardCounts();
$overdue = (new Reports(db()))->overdueList();
$pageTitle = 'Dashboard';
require __DIR__ . '/../Includes/Header.php';
?>
<div class="page-heading">
    <div>
        <h1>Dashboard</h1>
        <p class="muted">Live snapshot of catalogue, circulation and dues.</p>
    </div>
    <a class="button" href="<?= e(url('/Loans/Issue.php')) ?>">Issue a book</a>
</div>
<section class="cards">
    <article class="card"><div class="card-label">Titles</div><div class="metric"><?= (int) ($counts['total_books'] ?? 0) ?></div></article>
    <article class="card"><div class="card-label">Available copies</div><div class="metric"><?= (int) ($counts['available_copies'] ?? 0) ?></div></article>
    <article class="card"><div class="card-label">Active members</div><div class="metric"><?= (int) ($counts['active_members'] ?? 0) ?></div></article>
    <article class="card"><div class="card-label">Books out</div><div class="metric"><?= (int) ($counts['active_loans'] ?? 0) ?></div></article>
    <article class="card"><div class="card-label">Overdue</div><div class="metric"><?= (int) ($counts['overdue_loans'] ?? 0) ?></div></article>
    <article class="card"><div class="card-label">Pending reservations</div><div class="metric"><?= (int) ($counts['pending_reservations'] ?? 0) ?></div></article>
    <article class="card"><div class="card-label">Unpaid fines</div><div class="metric"><?= number_format((float) ($counts['unpaid_fines'] ?? 0), 2) ?></div></article>
</section>
<section class="panel">
    <h2>Overdue loans</h2>
    <div class="table-wrap"><table>
        <thead><tr><th>Member</th><th>Book</th><th>Due</th><th>Days late</th><th></th></tr></thead>
        <tbody>
        <?php foreach (array_slice($overdue, 0, 8) as $row): ?>
            <tr class="overdue-row">
                <td><strong><?= e($row['full_name']) ?></strong><br><span class="muted"><?= e($row['member_number']) ?></span></td>
                <td><?= e($row['title']) ?></td>
                <td><?= e($row['due_date']) ?></td>
                <td><?= (int) $row['days_overdue'] ?></td>
                <td><a class="button-small" href="<?= e(url('/Loans/Return.php?id=' . (int) $row['loan_id'])) ?>">Return</a></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$overdue): ?><tr><td class="empty" colspan="5">No overdue loans right now.</td></tr><?php endif; ?>
        </tbody>
    </table></div>
</section>
<?php require __DIR__ . '/../Includes/Footer.php'; ?>
