/* ------------------------ QUIZ LOGIC ------------------------ */


let questions = [];
let correctAnswers = [];

let currentQuestion = 0;
let score = 0;
let playerName = "";
let correctCount = 0;
let topic = "";

const loade = ['🤯','🧠','💀','🤓'];
const audioOk = new Audio('./assets/ok.mp3');
const audioKo = new Audio('./assets/ko.mp3');

function startQuiz() {
    topic = document.getElementById('topic-select').value;
    if (!topic) {
        document.getElementById('topic-select').classList.add('blink_me');
        setTimeout(() => document.getElementById('topic-select').classList.remove('blink_me'), 2000);
        return;
    }

    playerName = document.getElementById('player-select').value;
    if (!playerName) {
        document.getElementById('player-select').classList.add('blink_me');
        setTimeout(() => document.getElementById('player-select').classList.remove('blink_me'), 2000);
        return;
    }
    
    populateQuizData();

    document.getElementById('big-title').style.display = 'none';
    document.getElementById('player-selection').style.display = 'none';
    document.getElementById('topic-selection').style.display = 'none';
    document.getElementById('start-btn').style.display = 'none';
    document.getElementById('quiz-interface').classList.remove('hidden');
    showQuestion();
}


function showQuestion() {

    randomEmoji = Math.floor(Math.random() * loade.length);

    document.getElementById('question-container').innerHTML = "<span>" + loade[randomEmoji] +"</span>";
    document.getElementById('rispostina').innerHTML = '';
    document.querySelectorAll('button').forEach(button => button.classList.add('hidden'));

    // wait 2 seconds than show the new question
    new Promise(resolve => setTimeout(resolve, 1000))
        .then(() => {
            document.querySelectorAll('button').forEach(button => {
                button.classList.remove('hidden');
                button.classList.remove('sbagliato');
                button.classList.remove('corretto');
                button.classList.remove('locked');
                button.classList.remove('blink_me');
                button.classList.remove('sel');
                
            });
            document.getElementById('rispostina').innerHTML = '';
            document.getElementById('question-container').innerHTML = 
            `<h3>Domanda ${currentQuestion + 1} di ${questions.length}</h3><p>${questions[currentQuestion]}</p>`;
        });

    
}

function submitAnswer(answer) {
    if(document.getElementById('vero').classList.contains('locked')) {
        console.log('locked');
        return;
    }
    document.getElementById('vero').classList.add('locked');
    document.getElementById('falso').classList.add('locked');
    
    
        if(answer === correctAnswers[currentQuestion]) {
            if( answer === true) {
                document.getElementById('vero').classList.add('corretto');
                document.getElementById('vero').classList.add('sel');
            } else {
                document.getElementById('falso').classList.add('corretto');
                document.getElementById('falso').classList.add('sel');
            }
        }
        if(answer !== correctAnswers[currentQuestion]) {
            if( answer === true) {
                document.getElementById('falso').classList.add('corretto');
                document.getElementById('falso').classList.add('blink_me');
                document.getElementById('vero').classList.add('sel');
            } else {
                document.getElementById('vero').classList.add('corretto');
                document.getElementById('vero').classList.add('blink_me');
                document.getElementById('falso').classList.add('sel');
            }
            
        }
        

    if (answer === correctAnswers[currentQuestion]) {
        
        score += 10;
        correctCount++;
        // document.getElementById('rispostina').style.color = 'green';
        document.getElementById('rispostina').innerHTML = "BENE!";
        audioOk.play();
    } else {
        // document.getElementById('rispostina').style.color = 'red';
        // document.getElementById('rispostina').innerHTML = (answer ? 'VERO' : 'FALSO' ) + ' è SBAGLIATO!';

        audioKo.play();        
    }
    currentQuestion++;
    // wait 5 seconds than show the new question or send result
    new Promise(resolve => setTimeout(resolve, 3000))
        .then(() => {
            if (currentQuestion < questions.length) {
                showQuestion();
            } else {
                sendResults();
            }
        });

}

function sendResults() {
    fetch('quizapi.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name: playerName, score: score, correctAnswers: correctCount, topic: topic  })
    }).then(response => response.json())
        .then(data => function(){

        })
        .catch(error => console.error('Errore invio dati:', error));

    document.getElementById('quiz-interface').classList.add('hidden');
    document.getElementById('results').classList.remove('hidden');
    document.getElementById('SCORE').innerHTML = score;

    document.getElementById('final-score').innerHTML = 
        `${playerName}, hai risposto correttamente a ${correctCount} domande su ${questions.length}`;
}

function populatePlayerSelect() {
    fetch('players.json')
        .then(response => response.json())
        .then(players => {
            const select = document.getElementById('player-select');
            players.forEach(player => {
                const option = document.createElement('option');
                option.value = player.name;
                option.text = player.name;
                select.appendChild(option);
            });
        });
}

function populateQuizData() {
    topic = document.getElementById('topic-select').value;
    console.log('Loading topic:', './topics/' + topic + '/quizdata.json');
    fetch('./topics/' + topic + '/quizdata.json')
    .then(response => response.json())
    .then(data => {
        const questionsAndAnswers = data;
        questions = questionsAndAnswers.map(qa => {
            return qa.question.replace(/src\s*=\s*(['"])(.*?)(['"])/g, "src=$1./topics/" + topic + "/$2$3");
        });
        correctAnswers = questionsAndAnswers.map(qa => qa.answer);
    })
    .catch(error => {
        console.error('Errore caricamento dati:', error);
    });

    document.getElementById('rankingButton').href = './ranking.php?topic=' + document.getElementById('topic-select').value;

}






/* ------------------------ RANKING ------------------------ */



/**
 * Fetch and update leaderboard every 10 seconds
 */
function fetchResults() {
    fetch('players.json')
        .then(response => response.json())
        .then(players => {
            const topic = new URLSearchParams(window.location.search).get('topic') || 'default';
            fetch('./topics/'+ topic + '/results.json?'+ Math.random())
                .then(response => response.json())
                .then(data => updateLeaderboard(data,players));

        });
    setTimeout(fetchResults, 10000);
}

// const allowedEmoji = [
//     '😊','🙃','🤪','🤓','🤯','😴','💩','👻','👽','🤖',
//     '👾','👐','🖖','✌️','🤟','🤘','🤙','👋','🐭','🦕',
//     '🦖','🐉','⭐','🔥','🏓','🐮','👁️'];

function updateLeaderboard(results,players) {

    const scoreboardBody = document.getElementById('scoreboard-body');
    scoreboardBody.innerHTML = "";

    results.sort((a, b) => b.score - a.score);

    var i = 0;
    results.forEach((student, index) => {
        i++;
        if(i<=10){
            let icon = '';
            players.forEach(player => {
                if(player.name === student.name) {
                    icon = player.icon;
                }
            });
            let row = document.createElement('tr');
            row.innerHTML = `<td>${index + 1}</td><td>${student.name}${icon}</td><td>${student.score}</td>`;
            scoreboardBody.appendChild(row);
        }
    });
    if(i==0) {
        document.getElementById('top').classList.add('hidden');
    } else {
        document.getElementById('top').classList.remove('hidden');
    }
}


window.onload = function() {

    if( document.getElementById('stud') ) {
        // QUIZ PAGE

        // Populate players select
        populatePlayerSelect();

        // If only one topic is available, select it automatically
        const topicSelect = document.getElementById('topic-select');
        if (topicSelect.options.length === 2) {
            document.getElementById('topic-selection').style.display = 'none';
            topicSelect.options[1].selected = true;
        }
    } else {

        // RANKING PAGE
        fetchResults();
    }
        
}