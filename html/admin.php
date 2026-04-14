<?php require_once __DIR__ . '/include/i18n.php'; ?>
<?php require_once __DIR__ . '/server_includes.php'; ?>
<?php
$share = new ServerObject();
if (!isset($_COOKIE['mapi_key']) || $_COOKIE['mapi_key'] === '') {
    header("Location: index.php");
    exit();
}
$user = new UserObject($share->sql, $_COOKIE['mapi_key'], true);
if (!$user->ready || !$user->is_admin) {
    header("Location: index.php");
    exit();
}

$daily_calls   = $share->get_api_calls_per_day(30);
$monthly_calls = $share->get_api_calls_per_month(12);
$by_endpoint   = $share->get_api_calls_by_endpoint(30);
$top_users     = $share->get_api_top_users(30, 20);
$total_today   = $share->get_api_calls_total(1);
$total_30d     = $share->get_api_calls_total(30);
$total_all     = $share->get_api_calls_total();

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
        <h2><?php echo h('admin.title'); ?></h2>

        <h3><?php echo h('admin.summary'); ?></h3>
        <table class="table table-striped">
            <tr>
                <td><b><?php echo h('admin.today'); ?></b></td>
                <td><?php echo number_format($total_today); ?></td>
            </tr>
            <tr>
                <td><b><?php echo h('admin.last_30_days'); ?></b></td>
                <td><?php echo number_format($total_30d); ?></td>
            </tr>
            <tr>
                <td><b><?php echo h('admin.all_time'); ?></b></td>
                <td><?php echo number_format($total_all); ?></td>
            </tr>
        </table>

        <?php if (!empty($daily_calls)): ?>
        <h3><?php echo h('admin.daily_calls'); ?></h3>
        <div class="chart-container">
            <svg id="daily-chart" width="940" height="420"></svg>
        </div>
        <?php endif; ?>

        <?php if (!empty($monthly_calls)): ?>
        <h3><?php echo h('admin.monthly_calls'); ?></h3>
        <div class="chart-container">
            <svg id="monthly-chart" width="940" height="420"></svg>
        </div>
        <?php endif; ?>

        <?php if (!empty($by_endpoint)): ?>
        <h3><?php echo h('admin.by_endpoint'); ?></h3>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th><?php echo h('admin.endpoint'); ?></th>
                    <th><?php echo h('admin.calls'); ?></th>
                    <th><?php echo h('admin.percentage'); ?></th>
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

        <?php if (!empty($top_users)): ?>
        <h3><?php echo h('admin.top_users'); ?></h3>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th><?php echo h('admin.rank'); ?></th>
                    <th><?php echo h('admin.user_name'); ?></th>
                    <th><?php echo h('admin.user_email'); ?></th>
                    <th><?php echo h('admin.calls'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($top_users as $i => $u): ?>
                <tr>
                    <td><?php echo $i + 1; ?></td>
                    <td><?php echo htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo number_format($u['count']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<br/>

<?php include_once('footer.php'); ?>

<script src="./js/d3.min.js"></script>
<script>
var dailyData = <?php echo json_encode($daily_calls); ?>;
var monthlyData = <?php echo json_encode($monthly_calls); ?>;

function renderBarChart(svgId, data, labelKey, valueKey, xLabel, yLabel, title) {
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

renderBarChart('daily-chart', dailyData, 'date', 'count', 'Date', 'Calls', '<?php echo t('admin.daily_calls'); ?>');
renderBarChart('monthly-chart', monthlyData, 'month', 'count', 'Month', 'Calls', '<?php echo t('admin.monthly_calls'); ?>');
</script>

</body>
</html>
