# TweetAndGo
- A tweet archiver that circumvents using Twitter's dedicated API. 

# Features
- avoids spamming HTTP requests with randomized delays.
- uses Doctrine ORM to facilitate database interactions. 

# Demo
- The current implementation is able to parse up to ~3000 tweets for any account in <10 minutes. 
Currently, Twitter hard caps my methodology to this ~3000 figure. This can be sped up.
![alt text](https://raw.githubusercontent.com/Tenvolin/TweetAndGo/master/doc/count.png)
- Here is a quick view of this program's results.
![alt text](https://raw.githubusercontent.com/Tenvolin/TweetAndGo/master/doc/tweets.png)


# TODOs
- Ensure adherence to PSR code conventions: PSR1-4?
- Refactor to use Laravel; rid of Doctrine.
- Refactor using the web scraper design pattern.
- Fix filepath strings and settings such that the code can be deployed easily.
- Implement and deploy automated testing to ensure algorithm always works.
- Automate fetching of accounts of interest.
- The end goal of this project is to explore text semantic analysis and chatbot concepts I have in mind.