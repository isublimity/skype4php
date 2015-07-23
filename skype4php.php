<?php
class skype4php
{


    private static  $LOGIN_URL = "https://login.skype.com/login?client_id=578134&redirect_uri=https%3A%2F%2Fweb.skype.com";
    private static  $PING_URL = "https://web.skype.com/api/v1/session-ping";
    private static  $TOKEN_AUTH_URL = "https://api.asm.skype.com/v1/skypetokenauth";
    private static  $SUBSCRIPTIONS_URL = "https://%sclient-s.gateway.messenger.live.com/v1/users/ME/endpoints/SELF/subscriptions";
    private static  $MESSAGINGSERVICE_URL = "https://%sclient-s.gateway.messenger.live.com/v1/users/ME/endpoints/%s/presenceDocs/messagingService";
    private static  $ENDPOINTS_URL = "https://client-s.gateway.messenger.live.com/v1/users/ME/endpoints";
//    private static  $LOGOUT_URL = "https://login.skype.com/logout?client_id=578134&redirect_uri=https%3A%2F%2Fweb.skype.com&intsrc=client-_-webapp-_-production-_-go-signin";
    const POLL_URL = "https://%sclient-s.gateway.messenger.live.com/v1/users/ME/endpoints/SELF/subscriptions/0/poll";
    const CHAT_INFO_URL = "https://%sclient-s.gateway.messenger.live.com/v1/threads/%s/?view=msnp24Equivalent";
    const SEND_MESSAGE_URL = "https://%sclient-s.gateway.messenger.live.com/v1/users/ME/conversations/%s/messages";


    private $registrationToken;
    private $username;
    private $password;
    private $endpointId;
    private $path_cookies;
    private $skypeToken;
    public function __construct($user,$password,$path_cookies)
    {
        $this->username=$user;
        $this->password=$password;
        $this->path_cookies=$path_cookies;
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

    public function getCookieFile()
    {
        return rtrim($this->path_cookies,'/')."/cookie.txt";
    }
    private function page_curl($url,$fields_post=array(),$headers=array(),$custom_request='') {
        // Guzzle ?

        //??? @todo rwt

        $m=microtime(true);
        //open connection
        $ch = curl_init();

        //set the url, number of POST vars, POST data
        curl_setopt( $ch, CURLOPT_URL, $url);

//        CURLOPT_CUSTOMREQUEST, 'PUT')
        if ($custom_request)
        {
            curl_setopt( $ch, CURLOPT_CUSTOMREQUEST,'PUT');
        }
        else
        {
            curl_setopt( $ch, CURLOPT_POST, false);
        }
        if ($fields_post)
        {

            if (!$custom_request)
            {
                curl_setopt( $ch,CURLOPT_POST, 1);
            }
            if (is_array($fields_post))
            {
                curl_setopt( $ch,CURLOPT_POSTFIELDS, http_build_query($fields_post));
            }
            else
            {
                if (strtolower($fields_post)=='post')
                {
                    curl_setopt( $ch,CURLOPT_POSTFIELDS,"");
                }
                else
                {
                    curl_setopt( $ch,CURLOPT_POSTFIELDS,$fields_post);
                }

            }
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

        curl_setopt ($ch, CURLOPT_COOKIEFILE,$this->getCookieFile());
        curl_setopt ($ch, CURLOPT_COOKIEJAR,$this->getCookieFile());

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
        if (stripos($d[0]['http_code'],'200'))//??? @todo rwt
        {
            return true;
        }
        return false;
    }
    public function login()  {

        $skypetoken = $this->postToLogin($this->username, $this->password);
        // 1
        $this->getAsmToken($skypetoken);
        // 2
        $this->registerEndpoint($skypetoken);
        // 3
        return $this->ping();
    }
    private function buildRegistrationObject(){

        $privateInfo=array();
        $privateInfo['epname']='Skype4J';

        $publicInfo=array();
        $publicInfo['capabilities']="video|audio";
        $publicInfo['type']=1;
        $publicInfo['skypeNameVersion']="skype.com";
        $publicInfo['nodeInfo']="xx";
        $publicInfo['version']="908/1.6.0.288//skype.com";



        $registrationObject=array();
        $registrationObject['id']='messagingService';
        $registrationObject['type']='EndpointPresenceDoc';
        $registrationObject['selfLink']='uri';
        $registrationObject['publicInfo']=$publicInfo;
        $registrationObject['privateInfo']=$privateInfo;


        return $registrationObject;
    }

    private function registerEndpoint($skypeToken)  {
        $h=array('Authentication:skypetoken='.$skypeToken);

        $d=$this->page_curl(self::$ENDPOINTS_URL,'{}',$h);

        if ($d[2]['http_code']!==201)
        {
            throw new Exception('Cant registerEndpoint');
        }



//        int code = connection.getResponseCode();
//            if (code >= 301 && code <= 303 || code == 307) { //User is in a different cloud - let's go there
//                builder.setUrl(connection.getHeaderField("Location"));
//                updateCloud(connection.getHeaderField("Location"));
//                connection = builder.build();
//                code = connection.getResponseCode();
//            }

        $headers=$d[0];

        $SetRegistrationToken=$headers['Set-RegistrationToken'];


        if (!$SetRegistrationToken)
        {
            throw new Exception('not $SetRegistrationToken');
        }


        $splits=explode(';',$SetRegistrationToken);


        $tEndpointIds=explode('=',$splits[2]);


        //get : "Set-RegistrationToken"
        $tRegistrationToken=null;

        $this->skypeToken=$skypeToken;
        $this->registrationToken=$splits[0];
        $this->endpointId=$tEndpointIds[1];

//        echo "skypeToken:{$this->skypeToken}\nregistrationToken:{$this->registrationToken}\nEndpoint:{$this->endpointId}\nDone\n";
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
    private function getCloud()
    {
        return "bn1-";
    }
    private function withCloud($url,$array=false)
    {
        $a[0]=$this->getCloud();
        if (is_array($array) && sizeof($array)) $a=array_merge_recursive($a,$array);
        return vsprintf($url,$a);

    }
private function updateCloud( $anyLocation) {
//Pattern grabber = Pattern.compile("https?://([^-]*-)client-s");
//Matcher m = grabber.matcher(anyLocation);
//if (m.find()) {
//this.cloud = m.group(1);
//} else {
//    throw new IllegalArgumentException("Could not find match in " + anyLocation);
//}
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
        $html=$d[1];
        //??? <div class="messageIcon">Error</div><span>You have attempted to sign in with the wrong password too many times. Please try again later.
        //??? <div class="messageIcon">Error</div><span>We need to double-check your details. Please review this page and submit it again.</span></div>

        if (!stripos($html,'skypetoken'))
        {
            throw new Exception('Wrong answer on login , not skypetoken');
        }
        // skypetoken
        return $this->_postToLoginParseSkypetoken($html);


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

    public function getChats()
    {
        if (!sizeof($this->chats))
        {
            $this->initChats();
            if (!$this->chats) throw new Exception('cant load chats');

        }
        return $this->chats;
    }

    private $chats=array();
    private function initChats()
    {
        $head=array("Content-Type:application/json", "RegistrationToken:".$this->getRegistrationToken() );

        $irl='https://client-s.gateway.messenger.live.com/v1/users/ME/conversations?startTime='.time().'&pageSize=200&view=msnp24Equivalent&targetType=Passport|Skype|Lync|Thread';
        $result=$this->page_curl($this->withCloud($irl),false,$head);

        echo "--Initi chats------\n";

        $data=json_decode($result[1],true);
        if (!isset($data['conversations'])) throw new Exception('cant init chats');

        foreach ($data['conversations'] as $row)
        {
            if ($row['type']=='Conversation')
            {
                echo "find chat:".$row['id']."\n";
                $this->chats[$row['id']]=true;
            }

        }


    }
    private function subscribe()
    {

        $SubscriptionObject=array();
        $SubscriptionObject['channelType']='httpLongPoll';
        $SubscriptionObject['template']='raw';
        $SubscriptionObject['interestedResources']=array(
            "/v1/threads/ALL",
//           "/v1/users/ME/conversations/ALL/properties",
           "/v1/users/ME/conversations/ALL/messages",
           "/v1/users/ME/contacts/ALL"

        );
        $result=$this->page_curl($this->withCloud(self::$SUBSCRIPTIONS_URL),json_encode($SubscriptionObject),$head);

        if ($result[2]['http_code']!==201)
        {
            throw new Exception('subscribe to SUBSCRIPTIONS_URL false');
        }



        $url_message=$this->withCloud(self::$MESSAGINGSERVICE_URL,array(urlencode($this->endpointId)));



        $regObj=$this->buildRegistrationObject();


        $result=$this->page_curl($url_message,json_encode($regObj),$head,'PUT');


        if ($result[2]['http_code']!==200)
        {
            print_r($result);
            throw new Exception('false subscribe , $MESSAGINGSERVICE_URL = $url_message');
        }

//        $result=json_decode($result[1],true);
        print_r($result);
        for ($f=0;$f<10;$f++)
        {
            $this->poolThread();
        }

    }
    private function poolThread()
    {
        $url=$this->withCloud(self::POLL_URL);
        $head=array("Content-Type:application/json", "RegistrationToken:".$this->getRegistrationToken() );


        echo "$url\n";
        $result=$this->page_curl($url,'post',$head);

        $data=(json_decode($result[1],true));

        if (isset($data['eventMessages']))
        {
            echo "Recive\n";

            foreach ($data['eventMessages'] as $obj)
            {
                echo $obj['type']."\t".$obj['time']."\n";
                print_r($obj['resource']);
            }
        }
        else
        {
            echo "Skip!\n";
            print_r($data);
        }

        sleep(10);
    }

    private function getChatIdentity()
    {

    }
    private function loadChats()
    {
//        $head=array("Content-Type:application/json",  "RegistrationToken:".$this->getRegistrationToken() );
//        $url_message=$this->withCloud(self::CHAT_INFO_URL,array(urlencode($this->endpointId)));
//        $result=$this->page_curl($url_message,json_encode($regObj),$head,'PUT');
    }
    public function _bs_cribe()
    {

//        builder.setUrl(withCloud(MESSAGINGSERVICE_URL, URLEncoder.encode(endpointId, "UTF-8")));
//        builder.setMethod("PUT", true);
//        builder.setData(buildRegistrationObject().toString());
//        connection = builder.build();
//
//        code = connection.getResponseCode();
//        if (code != 200) {
//            throw generateException(connection);
//        }
//        pollThread = new Thread(String.format("Skype-%s-PollThread", username)) {
//        public void run() {
//        ConnectionBuilder poll = new ConnectionBuilder();
//                poll.setUrl(withCloud(POLL_URL));
//                poll.setMethod("POST", true);
//                poll.addHeader("RegistrationToken", registrationToken);
//                poll.addHeader("Content-Type", "application/json");
//                poll.setData("");
//                main:
//                while (loggedIn.get()) {
//                    try {
//                        HttpURLConnection c = poll.build();
//                        AtomicInteger code = new AtomicInteger(0);
//                        while (code.get() == 0) {
//                            try {
//                                code.set(c.getResponseCode());
//                            } catch (SocketTimeoutException e) {
//                                if (Thread.currentThread().isInterrupted()) {
//                                    break main;
//                                }
//                            }
//                        }
//
//                        if (code.get() != 200) {
//                            throw generateException(c);
//                        }
//
//                        InputStream read = c.getInputStream();
//                        String json = StreamUtils.readFully(read);
//                        if (!json.isEmpty()) {
//                            final JsonObject message = JsonObject.readFrom(json);
//                            if (!scheduler.isShutdown()) {
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
//                        }
//                    } catch (IOException e) {
//                        eventDispatcher.callEvent(new DisconnectedEvent(e));
//                        loggedIn.set(false);
//                    }
//                }
//            }
//        };
//        pollThread.start();
    }

    public function sendMessage($chat_id,$message_text)
    {
        echo "sendMessage($chat_id,$message_text)\n\n";
        $ms=microtime(true);
        $url=$this->withCloud(self::POLL_URL);
        $head=array("Content-Type:application/json", "RegistrationToken:".$this->getRegistrationToken() );

        $obj=array();
        $obj['content']=$message_text;
        $obj['contenttype']='text';
        $obj['messagetype']='RichText';
        $obj['clientmessageid']=$ms;

        $url=$this->withCloud(self::SEND_MESSAGE_URL,array(urlencode($chat_id)));

        $r=$this->page_curl($url,json_encode($obj),$head);
        if ($r[2]['http_code']!==201)
        {
            throw new Exception('Cant Send');
        }

        return true;
    }
    public function createChat()
    {
//        Validate.notNull(client, "Client must not be null");
//        Validate.isTrue(client instanceof SkypeImpl, String.format("Now is not the time to use that, %s", client.getUsername()));
//        Validate.notEmpty(identity, "Identity must not be null/empty");
//        if (identity.startsWith("19:")) {
//            if (identity.endsWith("@thread.skype")) {
//                return new ChatGroup((SkypeImpl) client, identity);
//            } else {
//                client.getLogger().info(String.format("Skipping P2P chat with identity %s", identity));
//                return null;
//            }
//        } else if (identity.startsWith("8:")) {
//            return new ChatIndividual((SkypeImpl) client, identity);
//        } else {
//            throw new IllegalArgumentException(String.format("Unknown group type with identity %s", identity));
//        }
    }
}