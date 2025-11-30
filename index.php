<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quizzone</title>
    <link rel="stylesheet" href="assets/style.css">
    <script src="assets/script.js"></script>
</head>
<body id='stud'>
    <h1 id='big-title' class='rainbow rainbow_text_animated'>QUIZZZONE</h1>
    <div id="topic-selection">
        <h2>Topic</h2>
        <select id="topic-select">
            <option value="">-- Choose --</option>
            <?php
            $files = glob('./topics/*', GLOB_ONLYDIR);
            foreach ($files as $file) {
                if(is_dir($file)) {
                    $topicName = basename($file);
                    echo "<option value='$topicName'>$topicName</option>";
                }
            }
            ?>
        </select>
        <div class='divider'>💥</div>
    </div>
    <div id="player-selection">
        <h2>Name</h2>
        <select id="player-select">
            <option value="">-- Choose --</option>
        </select>
        <div class='divider'>⭐</div>
    </div>
    <button id="start-btn" onclick="startQuiz()">START</button>
    <div id="quiz-interface" class="hidden">
        <div id="question-container"></div>
        <button onclick="submitAnswer(true)" id='vero'>TRUE</button>
        <button onclick="submitAnswer(false)" id='falso'>FALSE</button>
        <div id="rispostina"></div>
    </div>
    <div id="results" class="hidden">
        <div id="SCORE"></div>
        <div id="final-score"></div>
        <button onclick="document.location.href =document.location.href ;">PLAY AGAIN</button>
        <br><br>
        <a id='rankingButton' href="#" class="textlink rainbow rainbow_text_animated">RANKING</a>
    </div>
</body>
</html>
