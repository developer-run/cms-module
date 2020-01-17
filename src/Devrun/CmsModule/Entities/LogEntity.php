<?php

namespace Devrun\CmsModule\Entities;

use Devrun\CmsModule\Entities\UserEntity;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\MagicAccessors;
use Kdyby\Monolog\Logger;
use Nette\Utils\DateTime;

/**
 * Class LogEntity
 *
 * @ORM\Entity(repositoryClass="Devrun\CmsModule\Repositories\LogRepository")
 * @ORM\Table(name="log", indexes={@ORM\Index(name="inserted_idx", columns={"inserted"})})
 * @package FrontModule\Entities
 */
class LogEntity
{

    const ACTION_CREATED = 'created';
    const ACTION_UPDATED = 'updated';
    const ACTION_REMOVED = 'removed';
    const ACTION_OTHER = 'other';
    const ACTION_REDIRECT = 'redirect';
    const ACTION_LOGIN = 'login';
    const ACTION_REQUEST = 'request';
    const ACTION_EXCEPTION = 'exception';
    const ACTION_ADMIN = 'admin';
    const ACTION_MEMBER = 'member';
    const ACTION_ACCOUNT = 'account';
    const ACTION_DEVICE = 'device';
    const ACTION_DEVICE_GROUP = 'deviceGroup';
    const ACTION_CAMPAIGN = 'campaign';
    const ACTION_FORM = 'form';
    const ACTION_SEEN_BY_ADMIN = 'seenByAdmin';
    const ACTION_DIS_SEEN_BY_ADMIN = 'disSeenByAdmin';

    const TYPE_CHANGE_REQUEST = 'changeRequest';
    const TYPE_BLOG = 'blog';

    use Identifier;
    use MagicAccessors;


    /**
     * @var UserEntity
     * @ORM\ManyToOne(targetEntity="Devrun\CmsModule\Entities\UserEntity", cascade={"persist"})
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    protected $user;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    protected $role = "guest";

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    protected $target;

    /**
     * @var int
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $targetKey;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    protected $type;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    protected $action = self::ACTION_OTHER;

    /**
     * @var string
     * @ORM\Column(type="text")
     */
    protected $message = '';

    /**
     * @var array
     * @ORM\Column(type="json_array")
     */
    protected $context = [];

    /**
     * @var integer
     * @ORM\Column(type="smallint")
     */
    protected $level = Logger::INFO;

    /**
     * @var string
     * @ORM\Column(type="string", length=50)
     */
    protected $levelName = \Kdyby\Monolog\Tracy\MonologAdapter::INFO;

    /**
     * @var array
     * @ORM\Column(type="json_array")
     */
    protected $extra = [];


    /**
     * @var DateTime
     * @ORM\Column(type="datetime")
     */
    protected $inserted;

    /**
     * @param UserEntity $user
     */
    public function __construct($user, $target, $targetKey, $action, $type = NULL)
    {
        $this->user      = $user;
        $this->target    = $target;
        $this->targetKey = $targetKey;
        $this->action    = $action;
        $this->type      = $type;
        $this->inserted  = new DateTime;
    }


    /**
     * @param string $action
     */
    public function setAction($action)
    {
        $this->action = $action;
    }


    /**
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }


    /**
     * @param \DateTime $dateTime
     */
    public function setInserted($dateTime)
    {
        $this->inserted = $dateTime;
    }


    /**
     * @return \DateTime
     */
    public function getInserted()
    {
        return $this->inserted;
    }


    /**
     * @param string $message
     *
     * @return $this
     */
    public function setMessage($message)
    {
        $this->message = $message;
        return $this;
    }


    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }


    /**
     * @param string $type
     *
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }


    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }


    /**
     * @param string $target
     */
    public function setTarget($target)
    {
        $this->target = $target;
    }


    /**
     * @return string
     */
    public function getTarget()
    {
        return $this->target;
    }


    /**
     * @param int $targetKey
     */
    public function setTargetKey($targetKey)
    {
        $this->targetKey = $targetKey;
    }


    /**
     * @return int
     */
    public function getTargetKey()
    {
        return $this->targetKey;
    }


    /**
     * @param UserEntity $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }


    /**
     * @return UserEntity
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return string
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * @param string $role
     *
     * @return $this
     */
    public function setRole($role)
    {
        $this->role = lcfirst($role);
        return $this;
    }


    /**
     * @return mixed
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @param mixed $context
     *
     * @return $this
     */
    public function setContext($context)
    {
        $this->context = $context;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * @param int $level
     *
     * @return $this
     */
    public function setLevel($level)
    {
        $this->level = intval($level);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLevelName()
    {
        return $this->levelName;
    }

    /**
     * @param string $levelName
     *
     * @return $this
     */
    public function setLevelName($levelName)
    {
        $this->levelName = $levelName;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getExtra()
    {
        return $this->extra;
    }

    /**
     * @param array $extra
     *
     * @return $this
     */
    public function setExtra($extra)
    {
        $this->extra = $extra;
        return $this;
    }

    function __toString()
    {
        return $this->target . " " . $this->targetKey;
    }


}

