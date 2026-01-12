<?php
include("config.php");
?><!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quizzone</title>
    <link rel="stylesheet" href="assets/style.css">
    <script src="assets/script.js"></script>
</head>
<body id='stud'>
    <div id="quiz-bg-1" class="quiz-bg hidden"></div>
    <div id="quiz-bg-2" class="quiz-bg quiz-bg-2 hidden"></div>
    <div id="quiz-bg-3" class="quiz-bg quiz-bg-3 hidden"></div>
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
                    $topicLabel = $topicName;
                    $quizFile = $file . '/quizdata.json';
                    if (file_exists($quizFile)) {
                        $quizJson = json_decode(file_get_contents($quizFile), true);
                        if (is_array($quizJson)) {
                            if (isset($quizJson['titolo']) && is_string($quizJson['titolo'])) {
                                $title = trim($quizJson['titolo']);
                            } elseif (isset($quizJson['title']) && is_string($quizJson['title'])) {
                                $title = trim($quizJson['title']);
                            } else {
                                $title = '';
                            }
                            if ($title !== '') {
                                $topicLabel = $title;
                            }
                        }
                    }
                    echo "<option value='$topicName'>$topicLabel</option>";
                }
            }
            ?>
        </select>
        <div class='divider'>💥</div>
    </div>
    <div id="player-selection">
    <h2>Name</h2>
    <?php if ($ACTIVATE_FREE_USER) { ?>
        <input id="player-input" type="text" placeholder="Your name" maxlength="32">
    <?php } else { ?>
        <select id="player-select">
            <option value="">-- Choose --</option>
        </select>
    <?php } ?>
    <div class='divider'>💥</div>
</div>
<button id="start-btn" onclick="startQuiz()">START</button>
<script>
    window.QUIZ_FREE_USER = <?php echo $ACTIVATE_FREE_USER ? 'true' : 'false'; ?>;
</script>
    <?php
    if($ACTIVATE_CREATION) {
        ?><button id="create-btn" onclick="createQuiz()">CREA QUIZ</button><?php        
    }
    ?>
    <div id="create-status"></div>
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


