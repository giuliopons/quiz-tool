
<?php
/**
 * Ranking page for professors to view the top 10 quiz results for a selected topic.
 */
include("config.php");
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ranking</title>
    <link rel="stylesheet" href="assets/style.css">
    <script src="assets/script.js"></script>
    <script>
        window.onload = fetchResults;
    </script>
</head>
<body id='prof'>
    <div class='content'>
        <h1>TOP 10 RANKING</h1>
        <?php
        $availableTopics = getAvailableQuizTopics('./topics');
        $topic = isset($_GET['topic']) ? trim((string)$_GET['topic']) : 'default';
        if ($topic !== 'default') {
            $topic = trim(normalizeQuizTopicSlug($topic), '-');
        }
        if ($topic !== 'default' && !in_array($topic, $availableTopics, true)) {
            $topic = 'default';
        }
        if($topic == 'default') {
            ?>        
            <h2>Select Topic</h2>
            <select id="topic-select" onchange="document.location.href='ranking.php?topic='+this.value">
                <option value="">-- Choose --</option>
                <?php
                foreach ($availableTopics as $topicName) {
                    $topicEscaped = htmlspecialchars($topicName, ENT_QUOTES, 'UTF-8');
                    echo "<option value='$topicEscaped'>$topicEscaped</option>";
                }
                ?>
            </select>
            <?php
        } else {
            ?>
            <table id='top' border="1">
                <thead>
                    <tr>
                        <th colspan="3"><?php echo htmlspecialchars($topic); ?></th>
                    </tr>
                    <tr>
                        <th>RANK</th>
                        <th>WHO</th>
                        <th>SCORE</th>
                    </tr>
                </thead>
                <tbody id="scoreboard-body"></tbody>
            </table>
            <?php
        }
        ?>
        <br><br>
        <a href="index.php" class="textlink rainbow rainbow_text_animated">PLAY</a>
    </div>

<?php


    $topic = isset($topic) ? $topic : 'default';
    $hash = hash('crc32', $topic);
    $r = hexdec(substr($hash, 0, 2)) % 25 * 10;
    $g = hexdec(substr($hash, 2, 2)) % 25 * 10;
    $b = hexdec(substr($hash, 4, 2)) % 25 * 10;
    $rot = hexdec(substr($hash, 6, 2)) % 180;
    $style = "background-image: linear-gradient(-{$rot}deg, rgb($r, $g, $b) 50%, rgb(" . (255 - $r) . ", " . (255 - $g) . ", " . (255 - $b) . ") 50%);!important";

?>
<div class="bg" style="<?php echo $style; ?>"></div>
<div class="bg bg2" style="<?php echo $style; ?>"></div>
<div class="bg bg3" style="<?php echo $style; ?>"></div>

</body>
</html>
