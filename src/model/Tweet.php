<?php
/**
 * Created by PhpStorm.
 * User: Alex
 * Date: 2018-12-24
 * Time: 3:20 AM
 */

/**
 * @Entity
 * @table(name="tweets", options={"collate":"utf8mb4_general_ci", "charset":"utf8mb4"})
 **/
class Tweet
{
  /** @Id @Column(type="bigint") @GeneratedValue **/
  protected $id;
  /** @Column(type="string") unique=true **/
  protected $author;
  /** @Column(type="string", unique=true) **/
  protected $tweetId;
  /** @Column(type="string", length=1120) **/
  protected $message;
  /** @Column(type="datetime") **/
  protected $date;
  /** @Column(type="integer"), options={"unsigned":true} **/
  // 0: regular
  // 1: retweet
  protected $type;


  public function __construct($author, $tweetId, $message, $date, $type) {
    $this->author = $author;
    $this->tweetId = $tweetId;
    $this->message = $message;
    $this->date = $date;
    $this->type = $type;
  }

  /**
   * @return mixed
   */
  public function getTweetId()
  {
    return $this->tweetId;
  }

  /**
   * @param mixed $tweetId
   */
  public function setTweetId($tweetId): void
  {
    $this->tweetId = $tweetId;
  }

  /**
   * @return mixed
   */
  public function getAuthor()
  {
    return $this->author;
  }

  /**
   * @param mixed $author
   */
  public function setAuthor($author): void
  {
    $this->author = $author;
  }

  /**
   * @return mixed
   */
  public function getDate()
  {
    return $this->date;
  }

  /**
   * @param mixed $date
   */
  public function setDate($date)
  {
    $this->date = $date;
  }

  public function getMessage() {
    return $this->message;
  }

  public function setMessage($msg) {
    $this->message = $msg;
  }

  public function getId() {
    return $this->id;
  }


}