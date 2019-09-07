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
        ?>
        <div class="container">
            <br/>
            <h2>Overall Statistics</h2>
            <dl class="three-side-by-side">
                <dt>First Upload:</dt>
                <dd><?= $stats->earliestUpload()->format('Y-m-d') ?></dd>

                <dt>Most Recent Upload:</dt>
                <dd><?= $stats->latestUpload()->format('Y-m-d H:i:s') ?> UTC</dd>

                <dt>Total Sample Count:</dt>
                <dd><?= number_format($stats->countSamples()) ?></dd>
            </dl>
            <svg id="stats-by-year" style="height: 420px; width: 940px;"></svg>
            <?php
            $data = [];
            foreach ($uploadsByYear as $year => $count) {
                array_push($data, '{label: "' . $year . '", value: ' . $count . '}');
            }
            ?>
            <script src="/js/stats.js"></script>
            <script>
                createBarChart(d3.select('#stats-by-year'), [<?=implode(', ', $data)?>]);
            </script>

            <?php
            $fileTypeBreakdown = $stats->fileTypeBreakdown();
            //            var_dump($fileTypeBreakdown);
            ?>

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
        </div>
    </div>
</div>

<?php
include_once('footer.php');
?>

</body>
</html>
