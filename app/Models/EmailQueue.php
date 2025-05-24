<?php

namespace App\Models;

use App\Helpers\CommonModelTraits;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;

/**
 * Class EmailQueue
 *
 * @property $id
 * @property $from_email
 * @property $to_email
 * @property $message
 * @property $subject
 * @property $date_published
 * @property $last_attempt
 * @property $date_sent
 * @property $attempts
 * @property $status
 * @property $type
 * @property $model_id
 * @property $model_type
 * @property $created_at
 * @property $updated_at
 *
 * @package App
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class EmailQueue extends Model
{
  static $rules = [
    'status' => 'required',
    'type' => 'required',
  ];

  protected $perPage = 20;

  /**
   * Attributes that should be mass-assignable.
   *
   * @var array
   */
  protected $fillable = ['from_email', 'to_email', 'message', 'subject', 'date_published', 'last_attempt', 'date_sent', 'attempts', 'status', 'type', 'model_id', 'model_type'];

  public static function add($args = [])
  {

    if (!$args)
      return false;

    $mail = new self;
    if (isset($args['model'])) {
      $mail->model_id = $args['model']->id;
      $mail->model_type = get_class($args['model']);
    }
    $mail->from_email = env('MAIL_FROM_ADDRESS', 'smtp@itechnolabs.tech');
    $mail->to_email = $args['to'];
    $mail->subject = (isset($args['subject'])) ? $args['subject'] : "EmailQueue";
    $mail->type = (isset($args['type'])) ? $args['type'] : 0;
    $mail->date_published = now();
    $mail->last_attempt = now();
    $mail->attempts = 1;
    $mail->status = 0;
    $mail->message = View::make($args['view'], $args['viewArgs']);

    if ($mail->save()) {

      $response = $mail->sendNow($mail->to_email, $mail->subject, $args['view'], $args['viewArgs']);

      if ($response) {
        $mail->update(['status' => 1, 'date_sent' => now(), 'attempts' => 1, 'last_attempt' => now()]);
      }
    }
  }

  public function sendNow($toEmail, $subject, $viewName = 'mail', $param = array())
  {

    $flag = false;
    try {
      Mail::send($viewName, $param, function ($m) use ($toEmail, $subject) {
        $m->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
        $m->to($toEmail)->subject($subject);
        $flag = true;
      });
    } catch (\Throwable $ex) {
      $flag = false;
      ErrorLog::saveExceptionResponse($ex);
    }

    return $flag;
  }
}
