
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
    <h1>TOP 10 CLASSIFICONA</h1>
    <div class='content'>
    <table id='top' border="1">
        <thead>
            <tr>
                <th>RANK</th>
                <th>WHO</th>
                <th>SCORE</th>
            </tr>
        </thead>
        <tbody id="scoreboard-body"></tbody>
    </table>
	<br><br>
	<a href="index.php" class="textlink">GIOCA</a>
    </div>

<?php


    $topic = isset($_GET['topic']) ? $_GET['topic'] : 'default';
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
