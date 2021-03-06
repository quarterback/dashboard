<?php
/**
 * @copyright 2016 City of Bloomington, Indiana
 * @license http://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE.txt
 */
namespace Application\Models;

use Blossom\Classes\ActiveRecord;
use Blossom\Classes\Database;
use Blossom\Classes\Url;

class Card extends ActiveRecord
{

    protected $tablename = 'cards';
    protected $service;

    public static $comparisons = ['gt', 'gte', 'lt', 'lte'];

	/**
	 * Populates the object with data
	 *
	 * Passing in an associative array of data will populate this object without
	 * hitting the database.
	 *
	 * Passing in a scalar will load the data from the database.
	 * This will load all fields in the table as properties of this class.
	 * You may want to replace this with, or add your own extra, custom loading
	 *
	 * @param int|array $id
	 */
	public function __construct($id=null)
	{
		if ($id) {
			if (is_array($id)) {
				$this->data = $id;
			}
			else {
                $sql = 'select * from cards where id=?';

				$rows = parent::doQuery($sql, [$id]);
                if (count($rows)) {
                    $this->data = $rows[0];
                }
                else {
                    throw new \Exception('cards/unknown');
                }
			}
		}
		else {
			// This is where the code goes to generate a new, empty instance.
			// Set any default values for properties that need it here
		}
	}

	public function validate()
	{
        if (!$this->getDescription() || !$this->getService_id() || !$this->getMethod()) {
            throw new \Exception('missingRequiredFields');
        }

        if (!array_key_exists($this->getMethod(), $this->getService()->getMethods())) {
            throw new \Exception('cards/invalidMethod');
        }
	}

	public function save  () { parent::save  (); }
	public function delete()
	{
        $pdo   = Database::getConnection();
        $query = $pdo->prepare('delete from cardLog where card_id=?');
        $query->execute([$this->getId()]);

        parent::delete();
    }

	//----------------------------------------------------------------
	// Generic Getters & Setters
	//----------------------------------------------------------------
	public function getId()            { return parent::get('id');           }
	public function getDescription()   { return parent::get('description');  }
	public function getMethod()        { return parent::get('method');       }
	public function getParameters()    { return json_decode(parent::get('parameters'), true); }
	public function getTarget()        { return (int)parent::get('target');  }
	public function getComparison()    { return parent::get('comparison');   }
	public function getResponseKey()   { return parent::get('responseKey');  }
	public function getService_id()    { return parent::get('service_id');   }
	public function getService()       { return parent::getForeignKeyObject(__namespace__.'\Service', 'service_id'); }

	public function setDescription($s) { parent::set('description', $s); }
	public function setMethod     ($s) { parent::set('method',      $s); }
	public function setTarget     ($i) { parent::set('target', (int)$i); }
	public function setComparison ($s) { parent::set('comparison',  $s); }
	public function setResponseKey($s) { parent::set('responseKey', $s); }
	public function setService_id($id)     { parent::setForeignKeyField (__namespace__.'\Service', 'service_id', $id); }
	public function setService(Service $o) { parent::setForeignKeyObject(__namespace__.'\Service', 'service_id', $o ); }
	public function setParameters(array $p=null)
	{
        if ($p) { $p = json_encode($p); }
        parent::set('parameters', $p);
    }

	public function handleUpdate($post)
	{
        $fields = [
            'description', 'service_id', 'method', 'parameters',
            'target', 'comparison', 'responseKey'
        ];
        foreach ($fields as $f) {
            $set = 'set'.ucfirst($f);
            $this->$set($post[$f]);
        }
	}

	//----------------------------------------------------------------
	// Custom Functions
	//----------------------------------------------------------------
	/**
	 * Queries the configured webservice for a value as of a point in time
	 * If no date is provided, then the current datetime is used
	 *
	 * @param  DateTime $effectiveDate  The point in time to ask the service for a value
	 * @return ServiceResponse
	 */
	public function queryService(\DateTime $effectiveDate=null)
	{
        if (!$effectiveDate) { $effectiveDate = new \DateTime(); }

        $service = $this->getService();
        $method  = $this->getMethod();
        $params  = $this->getParameters();

        $params[ServiceInterface::EFFECTIVE_DATE] = $effectiveDate;

        $o = $service->factory();
        return $o->$method($params);
	}

	/**
	 * @return array
	 */
	public function getLatestLogEntry()
	{
        $result = $this->getLogEntries(1);
        if (count($result)) { return $result[0]; }
	}

	/**
	 * @return Blossom\Classes\Url
	 */
	public function getQueryUrl()
	{
        $service = self::$services[$this->getService()];
        $method  = $service['methods'][$this->getMethod()];
        $url     = new Url($service['base_url'].$method['uri']);

        $p = $this->getParameters();
        if (count($p)) {
            foreach ($p as $k=>$v) { $url->$k = $v; }
        }

        return $url;
	}

	public function logResponse(ServiceResponse $sr, \DateTime $logDate=null)
	{
        if (!$logDate) { $logDate = new \DateTime(); }

        $id = $this->getId();
        $ld = $logDate          ->format(parent::MYSQL_DATE_FORMAT);
        $ed = $sr->effectiveDate->format(parent::MYSQL_DATETIME_FORMAT);
        $v  = json_encode($sr->response);

        $sql = "insert into cardLog (card_id, logDate, effectiveDate, response) values(?, ?, ?, ?)
                on duplicate key update response=?, effectiveDate=?";
        $pdo     = Database::getConnection();
        $query   = $pdo->prepare($sql);
        $success = $query->execute([
            $id, $ld, $ed, $v,
            $v, $ed
        ]);
        if (!$success) {
            $error = $query->errorInfo();
            throw new \Exception($error[2]);
        }
	}

	/**
	 * @param  int    $limit
	 * @return array
	 */
	public function getLogEntries($limit=null)
	{
        $log = [];

        $sql = "select * from cardLog where card_id=? order by logDate desc";
        if ($limit) { $sql.= " limit ".(int)$limit; }

        $result = parent::doQuery($sql, [$this->getId()]);
        foreach ($result as $row) {
            $log[] = [
                'card_id'       => $row['card_id'],
                'logDate'       => new \DateTime($row['logDate'      ]),
                'effectiveDate' => new \DateTime($row['effectiveDate']),
                'response'      =>   json_decode($row['response'], true)
            ];
        }
        return $log;
	}

	/**
	 * @return array A Log Entry array
	 */
	public function getStatus(array $entry)
	{
        $target = (int)$this->getTarget();
        $value  = (int)$entry['response'][$this->getResponseKey()];

        $status = 'fail';
        switch ($this->getComparison()) {
            case 'gt' : if ($value >  $target) { $status = 'pass'; } break;
            case 'gte': if ($value >= $target) { $status = 'pass'; } break;
            case 'lt' : if ($value <  $target) { $status = 'pass'; } break;
            case 'lte': if ($value <= $target) { $status = 'pass'; } break;
        }
        return $status;
	}

	/**
	 * @return array [ parameters=>[], response=>[] ]
	 */
	public function getMethodDefinition()
	{
        $method  = $this->getMethod();
        $service = $this->getService();
        if ($method && $service) {
            return $service->getMethods()[$method];
        }
	}
}