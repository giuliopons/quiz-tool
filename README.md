# QUIZ TOOL

This is a simple quiz game for young students classrooms, made with PHP/js,css,html.
No database needed, just json files to store data.

## Instructions

Download everything, edit json files and upload on your desired folder in your website.
Navigate to that folder.

Users students play the quiz from their PC or device.

Results are shown in realtime on the teacher device or on LIM as a top hit score ranking page.

Emojiis make it a little funny.

You can try it here: https://www.barattalo.it/quiz

## Customize for a new topic and classroom
- create a subfolder inside `topics` with the topic name, inside that folder create a `quizdata.json` file (you can copy the structure from another `quizdata.json` file)
- edit the `players.json` file in the main `quiz` folder to add your students (you can remove existing names)
- make sure that PHP can write in the `topics` subfolders to properly write the `results.json` file

### More info

Questions and answers are stored in topics subfolders `quizdata.json` files.
Results are stored in topics subfolders `results.json` files.
Players are stored in the main folder `players.json` file.
In questions you can use HTML tags, short questions are preferred.
If you use `<img>` tags in HTML, store the images in the same subfolder topic.

## Next steps

Handle different results for different topic
