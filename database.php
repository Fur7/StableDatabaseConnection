<?php

/*
 * == Database class ==
 * Use this class to create a quick, save and
 * stable database connection to mutate.
 *
 * // Set-up the class. This has to be done only once in the project.
 * run::Credentials("basic","root","");
 *
 * // To run a query after setting the credentials.
 * run::Query("{INSERT QUERY}");
 *
 * // The function will return the data, if data is returned.
 * $data = run::Query("{SELECT QUERY}");
 *
 * ~ Ferry
 */

interface DatabaseConnectionModel {

  public function Connect();
  public function CloseConnect();
  public function writeToLog($write);

}

interface QueryModel {

  public function Run($query);
  public function Protect($query);

}

class DatabaseConnection implements DatabaseConnectionModel {

      //Basic vars
      protected $Conn;
      protected $DB;
      protected $User;
      protected $Pass;
      public $Host;
      public $Port;

      public function Connect()
      {
          // Get the credentials.
          $DatCon = json_decode(DatCon,true);
          $this->DB = $DatCon["database"];
          $this->User = $DatCon["username"];
          $this->Pass = $DatCon["password"];
          $this->Host = $DatCon["host"];
          $this->Port = $DatCon["port"];

          // Make the connection.
          try {
              $this->Conn = new PDO("mysql:host=" . $this->Host . ":" . $this->Port . ";dbname=" . $this->DB, $this->User, $this->Pass, array(PDO::ATTR_PERSISTENT => false, PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
              $this->Conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
              $this->Conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
              return true;
          } catch (PDOException $e) {
              $this->writeToLog($e->getMessage());
              return false;
          }
      }

      public function CloseConnect()
      {
          if ($this->QueryRun != null) {
              $this->QueryRun->closeCursor();
          }
          $this->Conn = null;
          $this->QueryRun = null;
      }

      public function writeToLog($write = "")
      {
        if($write!="") {
          $errorLog = dirname(__FILE__)."/errorlog.txt";
          if (!file_exists($errorLog)) {
            $fp = fopen($errorLog, "wb");
            fclose($fp);
          }
          file_put_contents($errorLog, date("d-m-Y, H:i:s")." - ".$write . "\n", FILE_APPEND);
        }
      }

}

class Query extends DatabaseConnection implements QueryModel {

    // Process vars
    public $LastID;
    public $DATA;
    public $QueryRun;

    /*
     * The function to run a quick query.
     * Use the DATA var to get the dataresponse out of the object.
     */

    public function Run($query = "")
    {
        $conStatus = $this->Connect();
        if($conStatus) {

          $this->DATA = null;

          // First determine if the query is a GET or a PUSH
          if (substr($query, 0, strlen("SELECT")) === "SELECT") {

              // It is a GET query
              try {
                  $this->QueryRun = $this->Conn->prepare($this->Protect($query));
                  $this->QueryRun->execute();
                  $this->DATA = $this->QueryRun->fetchAll(PDO::FETCH_ASSOC);
              } catch (PDOException $e) {
                  $this->writeToLog($e->getMessage());
              }
          } else {

              // It is a PUSH query
              try {
                  $sendQuery = $this->Conn->prepare($this->Protect($query));
                  $sendQuery->execute();
                  $this->LastID = $this->Conn->lastInsertId();
              } catch (PDOException $e) {
                  $this->writeToLog($e->getMessage());
              }
          }

          $this->CloseConnect();
          return $this->DATA;

        } else {
          return [];
        }
    }

    /*
     * Protect the given query by checking it on regular faults or attacks.
     */

    public function Protect($query = "") {
        // Try to find the strings in the query so we can check on ' use in this particular string.
        preg_match_all("~'(.*?)'(,* )~", str_replace(array("/*","<?","//","~",'`'),"",$query." "), $returnValue);
        $oldFields = $returnValue[1];
        $newFields = str_replace("'","\'",$oldFields);
        if(!empty($newFields)) {
          foreach($newFields as $key => $newField) {
            if(substr_count($newField, "'")) {
              $query = str_replace($oldFields[$key],$newField,$query);
            }
          }
        }
        return $query;
    }

}

/*
 * Run database mutations by given paths.
 */
class run {

  public static function Credentials($dat,$user,$pass,$host = "127.0.0.1",$port = 3306)
  {
    define("DatCon",json_encode(["database"=>$dat,"username"=>$user,"password"=>$pass,"host"=>$host,"port"=>$port]));
  }

  public static function Query($query)
  {
      $databaseQuery = new Query;
      return $databaseQuery->Run($query);
  }

}
