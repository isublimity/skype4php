<?php
class skype4php
{


    private static  $LOGIN_URL = "https://login.skype.com/login?client_id=578134&redirect_uri=https%3A%2F%2Fweb.skype.com";
    private static  $PING_URL = "https://web.skype.com/api/v1/session-ping";
    private static  $TOKEN_AUTH_URL = "https://api.asm.skype.com/v1/skypetokenauth";
    private static  $SUBSCRIPTIONS_URL = "https://client-s.gateway.messenger.live.com/v1/users/ME/endpoints/SELF/subscriptions";
    private static  $MESSAGINGSERVICE_URL = "https://client-s.gateway.messenger.live.com/v1/users/ME/endpoints/%s/presenceDocs/messagingService";
    private static  $ENDPOINTS_URL = "https://client-s.gateway.messenger.live.com/v1/users/ME/endpoints";
    private static  $LOGOUT_URL = "https://login.skype.com/logout?client_id=578134&redirect_uri=https%3A%2F%2Fweb.skype.com&intsrc=client-_-webapp-_-production-_-go-signin";

    private $registrationToken;
    private $username;
    private $password;
    private $endpointId;
    private $skypeToken;
    public function __construct($user,$password)
    {
        $this->username=$user;
        $this->password=$password;
    }
    private static function get_headers_from_curl_response($response)
    {
        $headers = array();

//        $header_text = substr($response, 0, strpos($response, "\r\n\r\n"));
        $header_text = $response;

        foreach (explode("\r\n", $header_text) as $i => $line)
            if ($i === 0)
                $headers['http_code'] = $line;
            else
            {
                $r= explode(': ', $line);

//                list ($key, $value) =$r;
                if (sizeof($r)==2)
                    $headers[$r[0]] = $r[1];
            }

        return $headers;
    }


    public static function page_curl($url,$fields_post=array(),$headers=array()) {
        // Guzzle ?


        $m=microtime(true);
        //open connection
        $ch = curl_init();

        //set the url, number of POST vars, POST data
        curl_setopt( $ch, CURLOPT_URL, $url);
        curl_setopt( $ch, CURLOPT_POST, false);

        if ($fields_post)
        {
            curl_setopt( $ch,CURLOPT_POST, 1);
            curl_setopt( $ch,CURLOPT_POSTFIELDS, http_build_query($fields_post));
            print_r($fields_post);

        }


        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
        curl_setopt( $ch, CURLOPT_HEADER, false);


        if ($headers)
        {
            curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers);
        }


        curl_setopt( $ch, CURLOPT_MAXREDIRS, 10);

        // don't take more than 5 seconds connecting and 10 seconds for a response
        curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 5 );
        curl_setopt( $ch, CURLOPT_TIMEOUT, 10 );

        curl_setopt ($ch, CURLOPT_COOKIEFILE, "/tmp/cookie.txt"); // Сюда будем записывать cookies, файл в той же папке, что и сам скрипт
        curl_setopt ($ch, CURLOPT_COOKIEJAR, "/tmp/cookie.txt");

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);

// Then, after your curl_exec call:

        $response = curl_exec($ch);


        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $header_size);
        $body = substr($response, $header_size);

        $end=microtime(true)-$m;
        $info=curl_getinfo($ch);
        $error=curl_error($ch);
        return array(self::get_headers_from_curl_response($header),$body,$info,$error,$end);
    }
    private  function randomUUID() {
        // php.net copypaste
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    public function ping()  {
        $d=$this->page_curl(self::$PING_URL,array(
            "sessionId"=>$this->randomUUID(),
        ),
            array('X-Skypetoken:'.$this->skypeToken)
            );

        return $d[0]['http_code'];


    }
    public function login()  {

        $skypetoken = $this->postToLogin($this->username, $this->password);
        // 1
        $this->getAsmToken($skypetoken);
        // 2
        $this->registerEndpoint($skypetoken);
        // 3
        $ping_ans=$this->ping();
        echo 'PING:'.$ping_ans."\n\n";
    }
    private function buildRegistrationObject()  {
//        JsonObject registrationObject = new JsonObject();
//        registrationObject.add("id", "messagingService");
//        registrationObject.add("type", "EndpointPresenceDoc");
//        registrationObject.add("selfLink", "uri");
//        JsonObject publicInfo = new JsonObject();
//        publicInfo.add("capabilities", "video|audio");
//        publicInfo.add("type", 1);
//        publicInfo.add("skypeNameVersion", "skype.com");
//        publicInfo.add("nodeInfo", "xx");
//        publicInfo.add("version", "908/1.6.0.288//skype.com");
//        JsonObject privateInfo = new JsonObject();
//        privateInfo.add("epname", "Skype4J");
//        registrationObject.add("publicInfo", publicInfo);
//        registrationObject.add("privateInfo", privateInfo);
//        return registrationObject;
    }
    private function buildSubscriptionObject()  {
//        JsonObject subscriptionObject = new JsonObject();
//        subscriptionObject.add("channelType", "httpLongPoll");
//        subscriptionObject.add("template", "raw");
//        JsonArray interestedResources = new JsonArray();
//        interestedResources.add("/v1/users/ME/conversations/ALL/properties");
//        interestedResources.add("/v1/users/ME/conversations/ALL/messages");
//        interestedResources.add("/v1/users/ME/contacts/ALL");
//        interestedResources.add("/v1/threads/ALL");
//        subscriptionObject.add("interestedResources", interestedResources);
//        return subscriptionObject;
    }
    private function registerEndpoint($skypeToken)  {
        $h=array('Authentication:skypetoken='.$skypeToken);

        $d=$this->page_curl(self::$ENDPOINTS_URL,array(),$h);


        $headers=$d[0];

        $SetRegistrationToken=$headers['Set-RegistrationToken'];


        if (!$SetRegistrationToken)
        {
            throw new Exception('not $SetRegistrationToken');
        }
//        echo "Fetch: \$SetRegistrationToken\n";        echo "$SetRegistrationToken\n\n-----\n";

        $SetRegistrationToken=explode(';',$SetRegistrationToken);

        $tEndpointIds=explode('=',$SetRegistrationToken[1]);


        //get : "Set-RegistrationToken"
        $tRegistrationToken=null;

        $this->skypeToken=$skypeToken;
        $this->registrationToken=$SetRegistrationToken[0];
        $this->endpointId=$tEndpointIds[1];



        echo "{$this->skypeToken}\n{$this->registrationToken}\n{ $this->endpointId}\nDone\n";


    }
    private function getAsmToken($skypeToken)  {
        $d=$this->page_curl(self::$TOKEN_AUTH_URL,array('skypetoken'=>$skypeToken));
        // @todo : check ans?
    }
    private function _postToLoginParseHtmlForm($html)
    {
        $data=array();
        $dom = new DomDocument();
        @$dom->loadHTML($html);

        $loginForm = $dom->getElementById("loginForm");
        $z=$loginForm->getElementsByTagName("input");

        foreach ($z as $input) {
            $name=$input->getAttribute('name');
            $value=$input->getAttribute('value');
            $data[$name]=$value;
        }

        $data['username']=$this->username;
        $data['password']=$this->password;
        $data['timezone_field'] = str_replace(':','|',date('P')); // ?data.put("timezone_field", new SimpleDateFormat("XXX").format(now).replace(':', '|'));
        $data['js_time']=time()/1000; // ?  data.put("js_time", String.valueOf(now.getTime() / 1000));

        return $data;
    }

    private function _postToLoginParseSkypetoken($html)  {

//        input type="hidden" name="skypetoken" value="eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImtpZCI6IjEifQ.eyJpYXQiOjE0Mzc0Mjg5NzIsImV4cCI6MTQzNzUxNTM3Miwic2t5cGVpZCI6ImlnMGlzdC5jb20iLCJzY3AiOjMxOCwiY3NpIjoiMCJ9.fT3sipfLx7VH6MlzMWXQ2X_eP4wlp0-edBRQok_wN3TG5kSWsn3v2EwhuMm801bLn-gjMl8dfVZkbGfgDmy3ANIegKUiWjPTWsS6Gdoo7oymgwHX8mCOOMq4RpjOv5ZPHny6sb-5VjNaij6Fq1Ny7nprCHfRfIg-FE-lp_6B2nlFrhoiZRVn0PwCPva1Sqe6BAl_5x2c8gX_nMwA"/> <input type="hidden" name="expires_in" value="86400"/> </form>
        $pattern = '/<input type="hidden" name="skypetoken" value="([^"]*)"\\/>/';
        preg_match($pattern,$html,$match);
        if (!$match[1])
        {
            throw new Exception('Cant find skypetoken');
        }
        return $match[1];
    }

    /**
     * result skypetoken
     *
     * @return mixed
     * @throws Exception
     */
    private function postToLogin()  {


        $result=$this->page_curl(self::$LOGIN_URL);
        $html=$result[1];

        if (stripos($html,'skypetoken'))
        {
            return $this->_postToLoginParseSkypetoken($html);
        }

        // parse html form
        $dataPost=$this->_postToLoginParseHtmlForm($html);

        print_r($dataPost);

        $d=$this->page_curl(self::$LOGIN_URL,$dataPost);
        //??? <div class="messageIcon">Error</div><span>You have attempted to sign in with the wrong password too many times. Please try again later.
        //??? <div class="messageIcon">Error</div><span>We need to double-check your details. Please review this page and submit it again.</span></div>

        if (!stripos($d,'skypetoken'))
        {
            throw new Exception('Wrong answer on login , not skypetoken');
        }
        // skypetoken
        return $this->_postToLoginParseSkypetoken($d);


    }
    public function getUsername()  {
        return $this->username;
    }
    public function getSkypeToken()  {
        return $this->skypeToken;
    }
    public function getRegistrationToken()  {
        return $this->registrationToken;
    }
    public function logout()  {
    //Jsoup.connect(LOGOUT_URL).cookies(this.cookies).get();
    //loggedIn.set(false);
    }

    public function getChat()
    {

    }
    public function subscribe()
    {
        //HttpsURLConnection subscribe = (HttpsURLConnection) new URL(SUBSCRIPTIONS_URL).openConnection();
//        subscribe.setRequestMethod("POST");
//        subscribe.setDoOutput(true);
//        subscribe.setRequestProperty("RegistrationToken", registrationToken);
//        subscribe.setRequestProperty("Content-Type", "application/json");
//        subscribe.getOutputStream().write(buildSubscriptionObject().toString().getBytes());
//        subscribe.getInputStream();
//        HttpsURLConnection registerEndpoint = (HttpsURLConnection) new URL(String.format(MESSAGINGSERVICE_URL, URLEncoder.encode(endpointId, "UTF-8"))).openConnection();
//        registerEndpoint.setRequestMethod("PUT");
//        registerEndpoint.setDoOutput(true);
//        registerEndpoint.setRequestProperty("RegistrationToken", registrationToken);
//        registerEndpoint.setRequestProperty("Content-Type", "application/json");
//        registerEndpoint.getOutputStream().write(buildRegistrationObject().toString().getBytes());
//        registerEndpoint.getInputStream();
//
//        Thread pollThread = new Thread() {
//        public void run() {
//                try {
//                    URL url = new URL("https://client-s.gateway.messenger.live.com/v1/users/ME/endpoints/SELF/subscriptions/0/poll");
//                    HttpsURLConnection c = null;
//                    while (loggedIn.get()) {
//                        try {
//                            c = (HttpsURLConnection) url.openConnection();
//                            c.setRequestMethod("POST");
//                            c.setDoOutput(true);
//                            c.addRequestProperty("Content-Type", "application/json");
//                            c.addRequestProperty("RegistrationToken", registrationToken);
//                            c.getOutputStream().write(new byte[0]);
//                            InputStream read = c.getInputStream();
//                            String json = StreamUtils.readFully(read);
//                            if (!json.isEmpty()) {
//                                final JsonObject message = JsonObject.readFrom(json);
//                                scheduler.execute(new Runnable() {
//                                    public void run() {
//                                        try {
//                                            JsonArray arr = message.get("eventMessages").asArray();
//                                            for (JsonValue elem : arr) {
//                                                JsonObject eventObj = elem.asObject();
//                                                String resourceType = eventObj.get("resourceType").asString();
//                                                if (resourceType.equals("NewMessage")) {
//                                                    JsonObject resource = eventObj.get("resource").asObject();
//                                                    String messageType = resource.get("messagetype").asString();
//                                                    MessageType type = MessageType.getByName(messageType);
//                                                    type.handle(SkypeImpl.this, resource);
//                                                } else if (resourceType.equalsIgnoreCase("EndpointPresence")) {
//                                                } else if (resourceType.equalsIgnoreCase("UserPresence")) {
//                                                } else if (resourceType.equalsIgnoreCase("ConversationUpdate")) { //Not sure what this does
//                                                } else if (resourceType.equalsIgnoreCase("ThreadUpdate")) {
//                                                    JsonObject resource = eventObj.get("resource").asObject();
//                                                    String chatId = resource.get("id").asString();
//                                                    Chat chat = getChat(chatId);
//                                                    if (chat == null) {
//                                                        chat = ChatImpl.createChat(SkypeImpl.this, chatId);
//                                                        allChats.put(chatId, chat);
//                                                        ChatJoinedEvent e = new ChatJoinedEvent(chat);
//                                                        eventDispatcher.callEvent(e);
//                                                    }
//                                                } else {
//                                                    logger.severe("Unhandled resourceType " + resourceType);
//                                                    logger.severe(eventObj.toString());
//                                                }
//                                            }
//                                        } catch (Exception e) {
//                                    logger.log(Level.SEVERE, "Exception while handling message", e);
//                                    logger.log(Level.SEVERE, message.toString());
//                                }
//                                    }
//                                });
//                            }
//                        } catch (IOException e) {
//                            eventDispatcher.callEvent(new DisconnectedEvent(e));
//                            loggedIn.set(false);
//                        }
//                    }
//                } catch (IOException e) {
//            e.printStackTrace();
//        }
//            }
//        };
//        pollThread.start();
    }
}