<?php

namespace App\Helpers;

use Auth;

class GoogleCalendarHelper
{
    private static $google_api   = 'https://www.googleapis.com/calendar/v3';
    private static $url          = '';
    private static $request      = [];
    public  static $errors       = false;
    private static $curl_method  = 'POST';
    private static $curl_data    = [];

    private static $start        = 'last month';
    private static $end          = 'next month';


    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
      /*
        TO-DO: validate users token, if invalid lets fix it and then come back? or what?
      */
    
    }


    /**
     * Return json results when updating a calendar
     *
     * @return object()
     */
    public static function createCalendar($calendar, $data=[])
    {
      return self::calendar($calendar, 'put', $data);
    }

    /**
     * Return json details of a calendar or if no calendar defined, a list of calendars
     *
     * @return object()
     */
    public static function readCalendar($calendar=false)
    {
      return self::calendar($calendar, 'get', false);
    }
    
    /**
     * Return json results when updating a calendar
     *
     * @return object()
     */
    public static function updateCalendar($calenda, $data=[])
    {
      return self::calendar($calendar, 'put', $data);
    }

    /**
     * Return json results when removing a calendar
     *
     * @return object()
     */
    public static function deleteCalendar($calendar)
    {
      return self::calendar($calendar, 'remove', false);
    }

    /**
     * Return json list of events for a calendar
     *
     * @return object()
     */
    public static function getEvents($calendar='primary')
    {
      self::setVar('url', '/calendars/'.$calendar.'/events');
      self::setVar('request', [
        'timeMin' => self::get_time(self::$start),
        'timeMax' => self::get_time(self::$end),
      ]);
      return self::get();
    }

    /**
     * Return json results when posting events to a calendar
     *
     * @return object()
     */
    public static function postEvents($calendar='primary', $post=[])
    {
      self::setVar('url', '/calendars/'.$calendar.'/events');
      return self::post($post);
    }

    /**
     * Return json from google api or false
     *
     * @return object()
     */
    private static function get()
    {
     self::setVar('curl_method', false);
     return self::curl();
    }

    /**
     * Return json from google api or false
     *
     * @return object()
     */
    private static function remove()
    {
      self::setVar('curl_method', 'DELETE');
      return self::curl();
    }

    /**
     * Return json from google api or false
     *
     * @return object()
     */
    private static function post($post=[])
    {
      self::setVar('curl_data', json_encode($post));
      self::setVar('curl_method', 'POST');
      return self::curl();
    }

    /**
     * Return json from google api or false
     *
     * @return object()
     */
    private static function put($put=[])
    {
      self::setVar('curl_data', json_encode($put));
      self::setVar('curl_method', 'PUT');
      return self::curl();
    }

    /**
     * Return array of curl headers with authorization token
     *
     * @return array()
     */
    private static function curl_headers()
    {
      return [
         'Content-type: application/json',
         'Authorization: Bearer ' . Auth::user()->token
      ];
    }

    /**
     * Return url for get/post call, has request vars + api key
     *
     * @return string
     */
    private static function get_url()
    {
      return self::$google_api.self::$url.self::build_request();
    }

    /**
     * Return result of curl get/post
     *
     * @return object/false
     */
    private static function curl()
    {
      $ch      = self::curl_open();
      $results = json_decode(curl_exec($ch));
      curl_close ($ch);
      return is_object($results) ? self::check_errors($results) : false;
    }

    /**
     * Return curl 
     *
     * @return curl_init();
     */
    private static function curl_open()
    {
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, self::get_url());
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_HTTPHEADER, self::curl_headers());
      if(self::$curl_method == 'POST') curl_setopt($ch, CURLOPT_POST, true);
      if(in_array(self::$curl_method, ['DELETE', 'PUT'])) curl_setopt($ch, CURLOPT_CUSTOMREQUEST, self::$curl_method);
      if(in_array(self::$curl_method, ['POST', 'PUT']))   curl_setopt($ch, CURLOPT_POSTFIELDS, self::$curl_data); 
      return $ch;
    }

    /**
     * Return boolean if error exists on curl result
     *
     * @return boolean
     */
    private static function check_errors($results)
    {
      return isset($results->error->errors) 
        ? self::setVar('errors', $results->error->errors) 
        : $results;
    }
  
    /**
     * Return request query
     *
     * @return http_build_query()
     */
    private static function build_request()
    {
      return '?'.http_build_query(array_merge(self::$request, ['key' => env('GOOGLE_API_KEY')]));
    }

    /**
     * Set class variables
     *
     * @return false
     */
    private static function setVar($var, $val)
    {
      self::$$var = $val;
      return false;
    }

    /**
     * Return date RFC3339
     *
     * @return false
     */
    private static function get_time($time='today')
    {
      return date("Y-m-d\TH:i:sP", strtotime($time));
    }

    /**
     * Return function based on method
     *
     * @return object()
     */
    private static function calendar($calendar, $method, $data)
    {
      self::setVar('url', ($calendar ? '/calendars/'.$calendar : '/users/me/calendarList'));
      return self::$method($data);
    }
}