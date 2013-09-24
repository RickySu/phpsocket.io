<?php
namespace PHPSocketIO\SessionStorage;
use Symfony\Component\HttpFoundation\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MetadataBag;

/**
 * Description of ArrayStorage
 *
 * @author ricky
 */
class MemoryStorage extends AbstractStorage
{
    protected static $sessions;

    /**
     * @var boolean
     */
    protected $started = false;

    /**
     * @var boolean
     */
    protected $closed = false;

    /**
     * Constructor.
     *
     * @param string      $name    Session name
     * @param MetadataBag $metaBag MetadataBag instance.
     */
    public function __construct($name = 'PHPSESSID', MetadataBag $metaBag = null)
    {
        $this->name = $name;
        $this->setMetadataBag($metaBag);
    }

    public function regenerate($destroy = false, $lifetime = null)
    {
        if (!$this->started) {
            $this->start();
        }

        if ($this->metadataBag) {
            $this->metadataBag->stampNew($lifetime);
        }

        if (isset(static::$sessions[$this->id])) {
            unset(static::$sessions[$this->id]);
        }

        $this->id = $this->generateId();

        if ($destroy) {
            $this->clear();
        }

        return true;
    }

    public function save()
    {
        static::$sessions[$this->id] = array('lastupdate'=> time(),'metadataBag' => $this->metadataBag, 'bags' => $this->bags);
    }

    public function start()
    {
        if ($this->started && !$this->closed) {
            return true;
        }

        if (empty($this->id)) {
            $this->id = $this->generateId();
        }

        $this->loadSession();

        return true;
    }

    protected function loadSession()
    {
        if (isset(static::$sessions[$this->id])) {
            $this->metadataBag = static::$sessions[$this->id]['metadataBag'];
            $this->bags = static::$sessions[$this->id]['bags'];
        }
    }
}
