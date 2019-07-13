<?php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;

class TweetController
{
  public function number()
  {
    return new Response(
      '<html><body>Lucky number: '. 4 .'</body></html>'
    );
  }
}