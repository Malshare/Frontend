<!DOCTYPE html>
<html lang="en">
<head>
    <?php include('header.php'); ?>
    <script src="js/d3.min-5.11.0.js"></script>
</head>
<body><?php include('nav.php') ?>

<div class="container" style="width:90%">
    <div class="jumbotron">
        <?php
        require('server_includes.php');
        require('include/stats.php');
        $share = new ServerObject();
        $stats = new Stats($share->sql);
        $uploadsByYear = $stats->uploadsByYear();
        $earliestUpload = $stats->earliestUpload();
        $latestUpload = $stats->latestUpload();
        ?>
        <div class="container">
            <br/>
            <h2>Overall Statistics</h2>
            <dl class="three-side-by-side">
                <?php
                if ($earliestUpload !== null) {
                    ?>
                    <dt>First Upload:</dt>
                    <dd><?= $earliestUpload->format('Y-m-d') ?></dd>
                    <?php
                }
                ?>
                <?php
                if ($latestUpload !== null) {
                    ?>
                    <dt>Most Recent Upload:</dt>
                    <dd><?= $latestUpload->format('Y-m-d H:i:s') ?> UTC</dd>
                    <?php
                }
                ?>
                <dt>Total Sample Count:</dt>
                <dd><?= number_format($stats->countSamples()) ?></dd>
            </dl>
            <svg id="stats-by-year" style="height: 420px; width: 940px;"></svg>
            <?php
            $domain = [];
            $max = 0;
            $data = [];
            foreach ($uploadsByYear as $year => $count) {
                array_push($domain, "'" . $year . "'");
                if ($count > $max) {
                    $max = $count;
                }
                array_push($data, '{key: "' . $year . '", value: ' . $count . '}');
            }
            ?>
            <script>
                function numberWithCommas(x) {
                    return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                }

                const barChartData = [<?=implode(', ', $data)?>];
                const margin = 80;
                const width = 940 - 2 * margin;
                const height = 420 - 2 * margin;
                const byYearSvg = d3.select('#stats-by-year');

                const chart = byYearSvg.append('g')
                    .attr('transform', `translate(${margin}, ${margin})`);
                const yScale = d3.scaleLinear()
                    .range([height, 0])
                    .domain([0, <?=$max?>]);

                chart.append('g')
                    .attr('class', 'chart-grid')
                    .call(d3.axisLeft()
                        .scale(yScale)
                        .tickSize(-width, 0, 0)
                        .tickFormat(''));

                chart.append('g')
                    .call(d3.axisLeft(yScale));

                const xScale = d3.scaleBand()
                    .range([0, width])
                    .domain([<?= implode($domain, ', ')?>])
                    .padding(0.2);

                chart.append('g')
                    .attr('transform', `translate(0, ${height})`)
                    .call(d3.axisBottom(xScale));

                chart.selectAll()
                    .data(barChartData)
                    .enter()
                    .append('rect')
                    .attr('id', (s) => 'by-year-chart-bar-' + s.key)
                    .attr('x', (s) => xScale(s.key))
                    .attr('y', (s) => yScale(s.value))
                    .attr('height', (s) => height - yScale(s.value))
                    .attr('width', xScale.bandwidth())
                    .on('mouseenter', function (actual, i) {
                        const textNodeId = 'by-year-chart-text-' + this.id.replace('by-year-chart-bar-', '');
                        document.getElementById(textNodeId).style.opacity = 1;
                    })
                    .on('mouseleave', function (actual, i) {
                        const textNodeId = 'by-year-chart-text-' + this.id.replace('by-year-chart-bar-', '');
                        document.getElementById(textNodeId).style.opacity = 0;
                    });

                byYearSvg.append('text')
                    .attr('x', -(height / 2) - margin)
                    .attr('y', margin / 3)
                    .attr('transform', 'rotate(-90)')
                    .attr('text-anchor', 'middle')
                    .text('Number of Samples');

                byYearSvg.append('text')
                    .attr('x', width / 2 + margin)
                    .attr('y', height + margin * 1.5)
                    .attr('text-anchor', 'middle')
                    .text('Year');

                byYearSvg.append('text')
                    .attr('x', width / 2 + margin)
                    .attr('y', 40)
                    .attr('text-anchor', 'middle')
                    .text('Uploaded Sample by Year');

                chart.selectAll()
                    .data(barChartData)
                    .enter()
                    .append('text')
                    .attr('id', (s) => 'by-year-chart-text-' + s.key)
                    .attr('x', (s) => xScale(s.key) + xScale.bandwidth() / 2)
                    .attr('y', (s) => yScale(s.value / 2) + 5)
                    .style('fill', 'white')
                    .style('opacity', 0)
                    .attr('text-anchor', 'middle')
                    .text((s) => numberWithCommas(s.value))
                    .on('mouseenter', function (actual, i) {
                        this.style.opacity = 1;
                    });
            </script>

            <h2>Last Week Statistics</h2>
            <?php
            // $startDate = new DateTime('2019-08-05');
            // $endDate = new DateTime('2019-08-11');
            $endDate = new DateTime();
            $endDate->setTimestamp(strtotime('yesterday midnight'));
            $startDate = new DateTime();
            $startDate->setTimestamp(strtotime('yesterday midnight'));
            $startDate->add(date_interval_create_from_date_string('-7 days'));

            $endDate->add(date_interval_create_from_date_string('1 day'));
            ?>
            <dl>
                <dt>Start Date</dt>
                <dd><?= $startDate->format('Y-m-d') ?></dd>

                <dt>End Date</dt>
                <dd><?= $endDate->format('Y-m-d') ?></dd>

                <dt>Samples:</dt>
                <dd><?= $stats->countSamples($startDate, $endDate) ?></dd>
            </dl>

            <h2>Breakdown by Magic</h2>
            <?php
            $fileTypeBreakdown = $stats->fileTypeBreakdown();
            $fileTypeForJavascript = [];
            foreach ($fileTypeBreakdown as $fileType => $count) {
                array_push($fileTypeForJavascript, "'" . $fileType . "': " . $count);
            }
            ?>
            <table class="table" style="width:30%; float: left;">
                <thead>
                <tr>
                    <th>Filetype</th>
                    <th>Count</th>
                </tr>
                </thead>
                <tbody>
                <?php
                foreach ($fileTypeBreakdown as $fileType => $count) {
                    ?>
                    <tr>
                        <td><?= $fileType ?></td>
                        <td><?= $count ?></td>
                    </tr>
                    <?php
                }
                ?>
                </tbody>
            </table>
            <div id="stats-file-magic" style="float:right;"></div>
            <script>
                const piChartData = {<?=implode(', ', $fileTypeForJavascript)?>};

                function createPiChart(selector, data, width, height) {
                    const radius = Math.min(width, height) / 2 - margin;
                    const svg = d3.select(selector).append("svg")
                        .attr("width", width).attr("height", height)
                        .append("g").attr("transform", "translate(" + width / 2 + "," + height / 2 + ")");
                    const color = d3.scaleOrdinal().domain(data).range(d3.schemeSet2);
                    const pie = d3.pie().value(function (d) {
                        return d.value;
                    });
                    const dataReady = pie(d3.entries(data));
                    const arcGenerator = d3.arc().innerRadius(0).outerRadius(radius);
                    svg.selectAll('mySlices').data(dataReady).enter()
                        .append('path').attr('d', arcGenerator)
                        .attr('fill', d => color(d.data.key))
                        .attr("stroke", "black").style("stroke-width", "1px").style("opacity", 0.7)
                        .on('mouseenter', (actual) => {
                            let caption = document.getElementById('pi-chart-caption');
                            caption.textContent = actual.data.key;
                            caption.style.opacity = 1;
                        })
                        .on('mouseleave', () => {
                            let caption = document.getElementById('pi-chart-caption');
                            caption.style.opacity = 0;
                        });
                    svg.append('text').attr('id', 'pi-chart-caption')
                        .attr('x', 0).attr('y', 10)
                        .attr("fill", "black").style('font-size', 32).attr('background-color', 'black')
                        .attr('text-anchor', 'middle')
                        .on('mouseenter', function (actual, i) {
                            this.style.opacity = 1;
                        });
                }

                createPiChart('#stats-file-magic', piChartData, 480, 480);
            </script>
            <br style="clear:both;"/>
        </div>
    </div>
</div>

<?php
include_once('footer.php');
?>

</body>
</html>
