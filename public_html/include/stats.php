<?php

class Stats
{
    private $db;

    function __construct(\mysqli $db)
    {
        $this->db = $db;
    }

    /**
     * @param DateTime|null $submittedFrom
     * @param DateTime|null $submittedUntil
     * @return int
     */
    function countSamples(DateTime $submittedFrom = null, DateTime $submittedUntil = null)
    {
        if ($submittedFrom === null) {
            if ($submittedUntil === null) {
                if (! ($stmt = $this->db->prepare('SELECT COUNT(*) FROM tbl_samples'))) {
                    return 0;
                }
            } else {
                if (! ($stmt = $this->db->prepare('SELECT COUNT(*) FROM tbl_samples WHERE (added < ?)'))) {
                    return 0;
                }
                $stmt->bind_param('i', $submittedUntil->getTimestamp());
            }
        } else {
            if ($submittedUntil === null) {
                if (! ($stmt = $this->db->prepare('SELECT COUNT(*) FROM tbl_samples WHERE (added >= ?)'))) {
                    return 0;
                }
                $stmt->bind_param('i', $submittedFrom->getTimestamp());
            } else {
                if (! ($stmt = $this->db->prepare(
                    'SELECT COUNT(*) FROM tbl_samples WHERE (added >= ?) AND (added < ?) '
                ))) {
                    return 0;
                }
                $stmt->bind_param(
                    'ii',
                    $submittedFrom->getTimestamp(),
                    $submittedUntil->getTimestamp()
                );
            }
        }

        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();

        return $count + 0;
    }

    /**
     * @param int $timestamp
     * @return DateTime|null
     */
    private function timestamp2DateTime($timestamp)
    {
        try {
            return new DateTime('@' . $timestamp);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * @return DateTime|null
     */
    function earliestUpload()
    {
        if (! ($stmt = $this->db->prepare('SELECT MIN(added) FROM tbl_samples'))) {
            return null;
        }
        $stmt->execute();
        $stmt->bind_result($earliestTimestamp);
        $stmt->fetch();

        return $this->timestamp2DateTime($earliestTimestamp);
    }

    /**
     * @return DateTime|null
     */
    function latestUpload()
    {
        if (! ($stmt = $this->db->prepare('SELECT MAX(added) FROM tbl_samples'))) {
            return null;
        }
        $stmt->execute();
        $stmt->bind_result($earliestTimestamp);
        $stmt->fetch();

        return $this->timestamp2DateTime($earliestTimestamp);
    }

    function uploadsByYear()
    {
        $earliestTimestamp = $this->earliestUpload();
        if ($earliestTimestamp === null) {
            return [];
        }
        $earliestYear = intval($earliestTimestamp->format('Y'));
        $thisYear = intval(date('Y'));
        if (! $earliestYear or ! $thisYear) {
            return [];
        }

        $ret = [];
        for ($i = $earliestYear; $i < $thisYear; $i++) {
            try {
                $startOfYear = new DateTime($i . '-01-01');
                $startOfNextYear = new DateTime(($i + 1) . '-01-01');
            } catch (Exception $e) {
                continue;
            }
            $ret[$i] = $this->countSamples($startOfYear, $startOfNextYear);
        }

        try {
            $startOfYear = new DateTime($thisYear . '-01-01');
            $startOfNextYear = new DateTime(($thisYear + 1) . '-01-01');
        } catch (Exception $e) {
            return $ret;
        }
        $ret[$thisYear] = $this->countSamples($startOfYear, $startOfNextYear);

        return $ret;
    }

    function uploadsByDay(DateTime $submittedFrom = null, DateTime $submittedUntil = null)
    {
        if (! ($stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM tbl_samples WHERE (added >= ?) AND (added < ?) '
        ))) {
            return 0;
        }
        $stmt->bind_param(
            'ii',
            $submittedFrom->getTimestamp(),
            $submittedUntil->getTimestamp()
        );
    }

    /**
     * @param int $days
     * @return array
     */
    function uploadsByDaySince($days)
    {
        $midnight = strtotime('today');
        $since = $midnight - $days * 24 * 60 * 60;

        $sql = <<<SQL
SELECT
    DATE_FORMAT(FROM_UNIXTIME(ts), "%Y-%m-%d") as order_date,
    COUNT(*) as count_at_day
FROM `tbl_uploads`
WHERE (ts >= ?) AND (ts < ?)
GROUP BY order_date
SQL;
        if (! ($stmt = $this->db->prepare($sql))) {
            return [];
        }
        $stmt->bind_param('ii', $since, $midnight);
        $stmt->execute();
        $stmt->bind_result($date, $count);
        $ret = [];
        while ($stmt->fetch()) {
            $ret[$date] = $count;
        }

        return $ret;
    }

    /**
     * @param DateTime|null $since
     * @param int $numberOfTypes
     * @return array
     */
    function fileTypeBreakdown(DateTime $since = null, $numberOfTypes = 8)
    {
        $numberOfTypes = $numberOfTypes + 0;
        if ($numberOfTypes <= 0) {
            $numberOfTypes = 30;
        }

        $sql = 'SELECT ftype, COUNT(*) as cnt FROM malshare_db.tbl_samples';
        if ($since !== null) {
            $sql .= ' WHERE (added > ?)';
        }
        $sql .= ' GROUP BY ftype ORDER BY cnt DESC LIMIT ' . $numberOfTypes;
        if (! ($stmt = $this->db->prepare($sql))) {
            return [];
        }
        if ($since !== null) {
            $stmt->bind_param('i', $since->getTimestamp());
        }

        $stmt->execute();
        $stmt->bind_result($fileMagic, $count);
        $ret = [];
        $sum = 0;
        $other = 0;
        while ($stmt->fetch()) {
            if (in_array($fileMagic, ['', '-', 'data'])) {
                $other += $count;
            } else {
                $ret[$fileMagic] = $count;
                $sum += $count;
            }
        }

        $totalSamples = $this->countSamples($since);
        $ret['Other'] = ($totalSamples - $sum) + $other;

        return $ret;
    }
}
