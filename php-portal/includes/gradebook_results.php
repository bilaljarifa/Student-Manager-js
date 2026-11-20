<?php if (!$resultRows): ?>
    <p class="empty">There are no results to show.</p>
<?php else: ?>
    <div class="table-responsive"><table class="table">
        <thead><tr>
            <?php if ($showLearner): ?><th scope="col">Student</th><?php endif; ?>
            <th scope="col">Subject</th><th scope="col">Final grade</th><th scope="col">Graded</th><th scope="col">Missing</th><th scope="col">Excused</th>
        </tr></thead>
        <tbody><?php foreach ($resultRows as $result): ?><tr>
            <?php if ($showLearner): ?><td><?= h(trim($result['first_name'] . ' ' . $result['last_name'])) ?></td><?php endif; ?>
            <td class="person-name"><?= h($result['subject_name']) ?></td>
            <td><?= $result['percentage'] === null ? 'Excused' : number_format((float) $result['percentage'], 2) . '%' ?></td>
            <td><?= (int) $result['graded_count'] ?></td><td><?= (int) $result['missing_count'] ?></td><td><?= (int) $result['excused_count'] ?></td>
        </tr><?php endforeach; ?></tbody>
    </table></div>
<?php endif; ?>
