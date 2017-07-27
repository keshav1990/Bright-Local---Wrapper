<?php
//require_once(APPPATH . 'third_party/bright/vendor/autoload.php');
//use BrightLocal\Api;
//use BrightLocal\Batches\V4 as BatchApi;
class Brightlocal {
  public $apiURL = 'https://tools.brightlocal.com/seo-tools/api';
  public $apiKey, $apiSecrete;
  function __construct() {
    $this->apiKey = '#####';
    $this->apiSecrete = '######';

  }
  public function get_sig_and_expires() {
    $expires = (int) gmdate('U') + 1800;
    $sig = base64_encode(hash_hmac('sha1', $this->apiKey . $expires, $this->apiSecrete, true));
    return array($sig, $expires);
  }

  /**
     * @param int $batchId
     * @return bool
     */
  public function commit($batchId) {
        $result = $this->call('/v4/batch', array(
            'batch-id' => $batchId
        ), 'PUT');
        return $result['success'];
    }

    /**
     * @param int $batchId
     * @return mixed
     */
  public function get_results($batchId) {
        return $this->call('/v4/batch', array(
            'batch-id' => $batchId
        ), 'GET');
    }
/// this is used to make api call acc to conditio like post , get put whatever by default we made it post

  public function call($method, $params = array(), $httpMethod = 'POST') {
    $method = str_replace('/seo-tools/api', '', $method);
// some methods only require api key but there's no harm in also sending
// sig and expires to those methods
    list($sig, $expires) = $this->get_sig_and_expires();
    $params = array_merge(array('api-key' => $this->apiKey, 'sig' => $sig, 'expires' => $expires), $params);
    $url =  $this->apiURL.'/' . ltrim($method, '/');
    try {
      if ($httpMethod === 'GET') {
        $result = $this->get($this->apiURL . '/' . ltrim($method, '/'), array('query' => $params));
      }
      else {
        $result = $this->post($url, array('form_params' => $params,'http_method'=>$httpMethod));
      }
    }
    catch (RequestException $e) {
      $result = $e->getResponse();

    }
      ///this is ued to return response
    return json_decode($result, true);
  }



  ///this is used to make the get request using the URL

  public function get($url, $parameters=array()) {
    $ch = curl_init();
  // print_r($parameters);
    $url = $url . '?' . http_build_query($parameters['query']);
    //echo $url;
    curl_setopt($ch, CURLOPT_URL,$url);
    curl_setopt($ch, CURLOPT_POST, false); // tell curl you want to post something
   // curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($parameters['query'])); // define what you want to post
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // return the output in string format
    $output = curl_exec($ch); // execute
    curl_close($ch); // close curl handle
   // echo $url;
    return ($output); // show output

  }

  /// this used to create a new batch request
  public function create($stopOnJobError = false) {
    $result = $this->call('/v4/batch', array('stop-on-job-error' => (int) $stopOnJobError));
    return $result['success'] ? $result['batch-id'] : false;
  }

  ///this is to post data

  public function post($url, $parameters) {
    $ch = curl_init();
    $url = $url ;
    curl_setopt($ch, CURLOPT_URL, $url);

    if($parameters['http_method']=='POST'){
    curl_setopt($ch, CURLOPT_POST, true); // tell curl you want to post something
    }
    else{
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $parameters['http_method']);
    }

    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($parameters['form_params'])); // define what you want to post
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // return the output in string format
    $output = curl_exec($ch); // execute
    curl_close($ch); // close curl handle
    return ($output); // show output
  }

  public function fetch_reviews_bydetail($Directory, $busdetail) {

    $batchId = $this->create();
    if ($batchId) {
      $result[] = '';
      foreach ($Directory as $localDirectory) {
        $result = $this->call('/v4/ld/fetch-reviews-by-business-data', array('batch-id' => $batchId, 'business-names' => $busdetail['bussiness_name'], 'city' => $busdetail['city'], 'postcode' => $busdetail['postcode'], 'street-address' => $busdetail['address'], 'local-directory' => $localDirectory, 'country' => $busdetail['country'], 'telephone' => $busdetail['telephone'], 'date-from' => $busdetail['date_from']));
      }
      $this->commit($batchId);
      return $batchId;
     
    }
  }
  public function fetch_locations() {
    $results = $this->call('/v1/clients-and-locations/locations/search',array(),'GET');

    return $results;
  }
  public function fetch_location_detail($loc) {
      $result = $this->call('/v1/clients-and-locations/locations/' . $loc,array(),'GET');
      return $result;
  }
}