<?php require_once __DIR__ . '/include/i18n.php'; ?>
<?php require_once __DIR__ . '/server_includes.php'; ?>
<?php
$share = new ServerObject();
require_once __DIR__ . '/include/stats.php';
$stats = new Stats($share->sql);

// Precomputed hourly by refresh_stats.py → tbl_stats_cache
$cache = $share->get_stats_cache();
$total_samples = isset($cache['total_samples']) ? (int) $cache['total_samples'] : 0;
$earliest      = isset($cache['earliest_upload']) && $cache['earliest_upload'] > 0
    ? (new DateTime('@' . $cache['earliest_upload'])) : null;
$latest        = isset($cache['latest_upload']) && $cache['latest_upload'] > 0
    ? (new DateTime('@' . $cache['latest_upload'])) : null;
$by_year       = isset($cache['uploads_by_year']) ? json_decode($cache['uploads_by_year'], true) : [];
$file_types    = isset($cache['file_type_breakdown']) ? json_decode($cache['file_type_breakdown'], true) : [];

// Live queries (fast — indexed counts and API rollup tables)
$daily_uploads    = $stats->uploadsByDaySince(30);
$monthly_api      = $share->get_api_calls_per_month(12);
$by_endpoint      = $share->get_api_calls_by_endpoint(30);
$registered_users = $share->get_registered_user_count();
$yara_rules       = $share->get_yara_rule_count();
$api_today        = $share->get_api_calls_total(1);
$api_30d          = $share->get_api_calls_total(30);
$api_all          = isset($cache['api_calls_all_time']) ? (int) $cache['api_calls_all_time'] : $share->get_api_calls_total();

$file_type_total = 0;
foreach ($file_types as $count) {
    $file_type_total += $count;
}

$endpoint_total = 0;
foreach ($by_endpoint as $ep) {
    $endpoint_total += $ep['count'];
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars(i18n_lang_value(), ENT_QUOTES, 'UTF-8'); ?>">
<head>
    <?php include('header.php'); ?>
    <style>
        .chart-container svg { width: 100%; }
        .chart-container rect { fill: #0088cc; }
        .chart-container rect:hover { fill: #005580; }
        .chart-grid line { stroke: #e0e0e0; stroke-dasharray: 2,2; }
        .chart-grid path { stroke-width: 0; }
    </style>
</head>

<body>
<?php include('nav.php') ?>
<div class="container">
    <div class="hero-unit">
        <h2><?php echo h('stats.title'); ?></h2>

        <h3><?php echo h('stats.summary'); ?></h3>
        <table class="table table-striped">
            <tr>
                <td><b><?php echo h('stats.total_samples'); ?></b></td>
                <td><?php echo number_format($total_samples); ?></td>
            </tr>
            <tr>
                <td><b><?php echo h('stats.registered_users'); ?></b></td>
                <td><?php echo number_format($registered_users); ?></td>
            </tr>
            <tr>
                <td><b><?php echo h('stats.yara_rules'); ?></b></td>
                <td><?php echo number_format($yara_rules); ?></td>
            </tr>
            <tr>
                <td><b><?php echo h('stats.collecting_since'); ?></b></td>
                <td><?php echo $earliest ? htmlspecialchars($earliest->format('Y-m-d'), ENT_QUOTES, 'UTF-8') : '-'; ?></td>
            </tr>
            <tr>
                <td><b><?php echo h('stats.latest_sample'); ?></b></td>
                <td><?php echo $latest ? htmlspecialchars($latest->format('Y-m-d H:i') . ' UTC', ENT_QUOTES, 'UTF-8') : '-'; ?></td>
            </tr>
            <tr>
                <td><b><?php echo h('stats.api_today'); ?></b></td>
                <td><?php echo number_format($api_today); ?></td>
            </tr>
            <tr>
                <td><b><?php echo h('stats.api_30_days'); ?></b></td>
                <td><?php echo number_format($api_30d); ?></td>
            </tr>
            <tr>
                <td><b><?php echo h('stats.api_all_time'); ?></b></td>
                <td><?php echo number_format($api_all); ?></td>
            </tr>
        </table>

        <?php if (!empty($by_year)): ?>
        <h3><?php echo h('stats.samples_by_year'); ?></h3>
        <div class="chart-container">
            <svg id="year-chart" width="940" height="420"></svg>
        </div>
        <?php endif; ?>

        <?php if (!empty($daily_uploads)): ?>
        <h3><?php echo h('stats.daily_uploads'); ?></h3>
        <div class="chart-container">
            <svg id="daily-chart" width="940" height="420"></svg>
        </div>
        <?php endif; ?>

        <?php if (!empty($file_types)): ?>
        <h3><?php echo h('stats.file_types'); ?></h3>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th><?php echo h('stats.file_type'); ?></th>
                    <th><?php echo h('stats.count'); ?></th>
                    <th><?php echo h('stats.percentage'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($file_types as $type => $count): ?>
                <tr>
                    <td><?php echo htmlspecialchars($type, ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo number_format($count); ?></td>
                    <td><?php echo $file_type_total > 0 ? number_format($count / $file_type_total * 100, 1) : '0'; ?>%</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>

        <?php if (!empty($by_endpoint)): ?>
        <h3><?php echo h('stats.by_endpoint'); ?></h3>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th><?php echo h('stats.endpoint'); ?></th>
                    <th><?php echo h('stats.count'); ?></th>
                    <th><?php echo h('stats.percentage'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($by_endpoint as $ep): ?>
                <tr>
                    <td><?php echo htmlspecialchars($ep['endpoint'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo number_format($ep['count']); ?></td>
                    <td><?php echo $endpoint_total > 0 ? number_format($ep['count'] / $endpoint_total * 100, 1) : '0'; ?>%</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>

        <?php if (!empty($monthly_api)): ?>
        <h3><?php echo h('stats.monthly_api'); ?></h3>
        <div class="chart-container">
            <svg id="monthly-chart" width="940" height="420"></svg>
        </div>
        <?php endif; ?>
    </div>
</div>

<br/>

<?php include_once('footer.php'); ?>

<script src="./js/d3.min.js"></script>
<script>
var yearData = <?php echo json_encode(array_map(function($year, $count) {
    return array('label' => (string) $year, 'value' => $count);
}, array_keys($by_year), array_values($by_year))); ?>;

var dailyData = <?php echo json_encode(array_map(function($date, $count) {
    return array('label' => $date, 'value' => $count);
}, array_keys($daily_uploads), array_values($daily_uploads))); ?>;

var monthlyData = <?php echo json_encode($monthly_api); ?>;

function renderBarChart(svgId, data, labelKey, valueKey, yLabel, title) {
    var items = data.map(function(d) { return { label: d[labelKey], value: d[valueKey] }; });
    if (items.length === 0) return;

    var svg = d3.select('#' + svgId);
    var margin = 80;
    var width = 940 - 2 * margin;
    var height = 420 - 2 * margin;

    var chart = svg.append('g').attr('transform', 'translate(' + margin + ',' + margin + ')');

    var yScale = d3.scaleLinear()
        .range([height, 0])
        .domain([0, d3.max(items, function(d) { return d.value; })]);
    chart.append('g')
        .attr('class', 'chart-grid')
        .call(d3.axisLeft().scale(yScale).tickSize(-width, 0, 0).tickFormat(''));
    chart.append('g').call(d3.axisLeft(yScale));

    var xScale = d3.scaleBand()
        .range([0, width])
        .domain(items.map(function(d) { return d.label; }))
        .padding(0.2);
    chart.append('g')
        .attr('transform', 'translate(0,' + height + ')')
        .call(d3.axisBottom(xScale))
        .selectAll('text')
        .attr('transform', 'rotate(-45)')
        .style('text-anchor', 'end');

    chart.selectAll('.bar').data(items).enter()
        .append('rect')
        .attr('class', 'bar')
        .attr('x', function(d) { return xScale(d.label); })
        .attr('y', function(d) { return yScale(d.value); })
        .attr('height', function(d) { return height - yScale(d.value); })
        .attr('width', xScale.bandwidth());

    svg.append('text')
        .attr('x', -(height / 2) - margin)
        .attr('y', margin / 3)
        .attr('transform', 'rotate(-90)')
        .attr('text-anchor', 'middle')
        .text(yLabel);

    svg.append('text')
        .attr('x', width / 2 + margin)
        .attr('y', 40)
        .attr('text-anchor', 'middle')
        .text(title);
}

renderBarChart('year-chart', yearData, 'label', 'value', 'Samples', '<?php echo t('stats.samples_by_year'); ?>');
renderBarChart('daily-chart', dailyData, 'label', 'value', 'Uploads', '<?php echo t('stats.daily_uploads'); ?>');
renderBarChart('monthly-chart', monthlyData, 'month', 'count', 'Calls', '<?php echo t('stats.monthly_api'); ?>');
</script>

</body>
</html>
