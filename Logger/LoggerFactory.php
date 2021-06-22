<?php

/*
 * This file is part of the XiideaEasyAuditBundle package.
 *
 * (c) Xiidea <http://www.xiidea.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Xiidea\EasyAuditBundle\Logger;

use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Xiidea\EasyAuditBundle\Entity\BaseAuditLog;
use Xiidea\EasyAuditBundle\Exception\InvalidServiceException;
use Xiidea\EasyAuditBundle\Traits\ServiceContainerGetterMethods;

class LoggerFactory
{
    use ContainerAwareTrait;
    use ServiceContainerGetterMethods;

    /** @var LoggerInterface[] */
    static private $loggers = array();

    private $loggersChannel;

    public function __construct(array $channel = array())
    {
        $this->loggersChannel = $channel;
    }

    /**
     * @param null|\Xiidea\EasyAuditBundle\Entity\BaseAuditLog $eventInfo
     */
    public function executeLoggers($eventInfo)
    {
        if (empty($eventInfo)) {
            return;
        }

        foreach (self::$loggers as $id => $logger) {
            if ($this->isValidLoggerForThisEvent($eventInfo, $logger, $id)) {
                $logger->log($eventInfo);
            }
        }
    }

    /**
     * @param string $loggerName
     * @param LoggerInterface $logger
     * @throws InvalidServiceException
     */
    public function addLogger($loggerName, $logger)
    {
        if ($logger instanceof LoggerInterface) {
            self::$loggers[$loggerName] = $logger;
        } elseif ($this->isDebug()) {
            throw new InvalidServiceException('Logger Service must implement' . __NAMESPACE__ . "LoggerInterface");
        }
    }

    /**
     * @return \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected function getContainer()
    {
        return $this->container;
    }

    /**
     * @param string $id
     * @param string $level
     * @return bool
     */
    private function isChannelRegisterWithLogger($id, $level)
    {
        if (!isset($this->loggersChannel[$id])) {
            return true;
        }

        if ($this->isChannelTypeOf('inclusive', $id)) {
            return $this->levelExistsInList($level, $id);
        }

        if ($this->isChannelTypeOf('exclusive', $id)) {
            return !$this->levelExistsInList($level, $id);
        }

        return false;
    }

    /**
     * @param string $type
     * @param string $id
     * @return bool
     */
    private function isChannelTypeOf($type, $id)
    {
        return $this->loggersChannel[$id]['type'] == $type;
    }

    /**
     * @param string $level
     * @param string $id
     * @return bool
     */
    private function levelExistsInList($level, $id)
    {
        return in_array($level, $this->loggersChannel[$id]['elements']);
    }

    /**
     * @param BaseAuditLog $eventInfo
     * @param $logger
     * @param $id
     * @return bool
     */
    protected function isValidLoggerForThisEvent(BaseAuditLog $eventInfo, $logger, $id)
    {
        return $logger instanceof LoggerInterface && $this->isChannelRegisterWithLogger($id, $eventInfo->getLevel());
    }
}
