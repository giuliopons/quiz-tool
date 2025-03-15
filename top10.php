
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz di Robotica - Monitoraggio</title>
    <link rel="stylesheet" href="style.css">
    <script>
        function fetchResults() {
            fetch('risultati.json?'+ Math.random())
                .then(response => response.json())
                .then(data => updateLeaderboard(data))
                .catch(error => console.error('Errore nel recupero dei risultati:', error));
        }

        const allowedEmoji = ['😊','🙃','🤪','🤓','🤯','😴','💩','👻','👽','🤖','👾','👐','🖖','✌️','🤟','🤘','🤙','👋','🐭','🦕','🦖','🐉','⭐','🔥','🏓','🐮','👁️'];

        function updateLeaderboard(results) {
            const scoreboardBody = document.getElementById('scoreboard-body');
            scoreboardBody.innerHTML = "";

            results.sort((a, b) => b.score - a.score);

            var i = 0;
            results.forEach((student, index) => {
                i++;
                if(i<=10){
                    let random = Math.floor(Math.random() * allowedEmoji.length);
                    
                    let row = document.createElement('tr');
                    row.innerHTML = `<td>${index + 1}</td><td>${student.name}${allowedEmoji[random]}</td><td>${student.score}</td>`;
                    scoreboardBody.appendChild(row);
                    
                }
            });
            if(i==0) {
                document.getElementById('top').classList.add('hidden');
            } else {
                document.getElementById('top').classList.remove('hidden');
            }
        }

        setInterval(fetchResults, 30000);
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


<div class="bg"></div>
<div class="bg bg2"></div>
<div class="bg bg3"></div>

</body>
</html>
