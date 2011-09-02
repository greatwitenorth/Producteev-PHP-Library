<?php
/**
 * Author:  Angelo R.
 * Link:    http://xangelo.ca
 *
 * Modified by: Nick Verwymeren
 * Link:    http://makesomecode.com
 * Version: 1.0
 *
 * This class allows you to access the Producteev api through PHP. It's very simple but currently only supports
 * JSON resultsets.
 *
 * You'll find examples at the bottom
 *
 * Thanks for downloading, I've provided this as part of the MIT licensing agreement so that you are pretty much
 * free to do whatever you want with it.
 *
 */
 
class Producteev {

    private $api_key;
    private $api_secret;
    private $base_url;
    private $return_type;
    private $params;
    private $token;

    private $dump;

    /**
     * Basically, you can either set it here if you only make occasional calls to the api, or (preferred method)
     * just set it directly in the class by modifying $api_key and $api_secret directly. That way you're not
     * setting it as part of every instantiation.
     * @param string $key your api key
     * @param string $secret your api secret
     */
    public function __construct($key = '',$secret = '') {
        if($this->api_key == null) {
            if($key == '') {
                die('Producteev API Key not set');
            }
            else {
                $this->api_key = $key;
            }
        }
        if($this->api_secret == null) {
            if($secret == '') {
                die('Producteev API Secret not set');
            }
            else {
                $this->api_secret = $secret;
            }
        }

        $this->base_url = 'https://api.producteev.com/';
        $this->params = array();
        $this->needed_params = array();
        $this->token = null;
    }

    /**
     * At the moment it only supports json, so there really isn't a need to even USE this. The Execute() method
     * will default to json anyways.
     * @param  string $type one of json |
     * @return void
     */
    public function SetReturnType($type) {
        if(in_array($type,array('json'))) {
            $this->return_type = $type;
        }
        else {
            die('Invalid request type');
        }
    }

    /**
     * This method is used to set any variable information. So, something that would change on every request would
     * go here. After every request is made this is wiped clean to prepare for the next request.
     * @param  string
     * @param value
     * @return void
     */
    public function Set($key,$val = '') {
        if(is_array($key)) {
            foreach($key as $k=>$v) {
                $this->Set($k,$v);
            }
        }
        else {
            $this->params[$key] = $val;
        }
    }

    public function Get($key) {
        return $this->params[$key];
    }

    /**
     * Logs in
     * @param  string $email
     * @param  string $password
     * @return stdClass
     */
    public function Login($email,$password) {
        $this->Set('email',$email);
        $this->Set('password',$password);


        $d = $this->Execute('users/login');
        $this->token = $d->login->token;
        return $d;
    }

    /**
     * Executes whatever action is passed to it.
     *
     * This method ensures that the url is properly formatted. It doesn't actually perform an direct operation on
     * the url, instead it will call $this->Curl() which will actually perform the request and deal with the
     * results.
     * @param  string $action One of the supported actions from the api
     * @return mixed An stdObject containing the results of your request
     */
    public function Execute($action) {
        $url = $this->base_url.$action;
        $url .= ($this->return_type == null)?'.json?':'.'.$this->return_type.'?';
        $url .= $this->GenerateUrl();

        return $this->Curl($url);
    }

    /**
     * At the moment this just performs the request and then returns the data (after storing the token if there
     * is one)
     * @param  $url
     * @return mixed
     */
    private function Curl($url){
        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,    // Shortcut for now
        ));
        $result = curl_exec($ch);

        $data = json_decode($result);

        switch(curl_getinfo($ch,CURLINFO_HTTP_CODE)) {
            case 200:
                // Patch to ensure that the token is always stored
                if($data->login != null && $data->login->token != null) {
                    $this->token = $data->login->token;
                }

                break;
            case 403:
                echo $url.'<br>';
                echo $data->error->message.'<br>';
                echo '<pre>'.print_r($this->dump,true).'</pre>';
                break;
            default:
                die('<div>'.curl_error($ch).'</div>');
        }

        curl_close($ch);
        $this->ClearParams();
        return $data;
    }

    /**
     * Just deletes the params for now.
     * @return void
     */
    public function ClearParams() {
        $this->params = array();
    }

    /**
     * Generates the URL through concatination of params and persistent params. It generates the signature first
     * and then proceeds to mush all the parameters together.
     * @return string
     */
    private function GenerateUrl() {
        $this->Set('api_key',$this->api_key);
        if($this->token != null) {
            $this->Set('token',$this->token);
        }
        // Needs to be called after all params are set
        $this->GenerateSignature();
        $str = '';

        // Creates the final query string
        foreach($this->params as $x=>$d) {
            $str .= $x.'='.urlencode($d).'&';
        }
       return substr($str,0,strlen($str)-1);
    }

    /**
     * Essentially the same code that is present on the api website, this just constructs the signature
     * @return void
     */
    public function GenerateSignature() {
        $str = '';
        ksort($this->params);   // THIS IS VITAL!

        foreach ($this->params as $k=>$v) {
            if(is_string($v)) {
                $str .= "$k$v";
            }
        }
        $str .= $this->api_secret;
        $str = stripslashes($str);
        $this->Set('api_sig',md5($str));
    }
}

/*
 * Example:
 * $p = new Producteev('Your key','Your secret');
 * $p->SetReturnType('json');
 * $p->Set(array(
 *      'email'=>'xangelo@gmail.com',
 *      'password'=>'08374f')
 * );
 * $data = $p->Execute('users/login');
 * $data = $p->Execute('dashboards/show_list');
 *
 * echo '<pre>'.print_r($data,true).'</pre>';
 */
