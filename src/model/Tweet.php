<?php
/**
 * Created by PhpStorm.
 * User: Alex
 * Date: 2018-12-24
 * Time: 3:20 AM
 */

/**
 * @Entity
 * @table(name="tweets")
 **/
class Tweet
{
  /** @Id @Column(type="bigint") @GeneratedValue **/
  protected $id;
  /** @Column(type="string", unique=true) @GeneratedValue **/
  protected $tweetId;
  /** @Column(type="string") @GeneratedValue **/
  protected $message;
  /** @Column(type="datetime") @GeneratedValue **/
  protected $date;

  public function __construct($tweetId) {
    $this->tweetId = $tweetId;
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

  public function getId() {
    return $this->id;
  }

  public function getMessage() {
    return $this->message;
  }

  public function setMessage($msg) {
    $this->message = $msg;
  }



}