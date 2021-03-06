<?php
/*************************************************************************************/
/*      This file is part of the Thelia package.                                     */
/*                                                                                   */
/*      Copyright (c) OpenStudio                                                     */
/*      email : dev@thelia.net                                                       */
/*      web : http://www.thelia.net                                                  */
/*                                                                                   */
/*      For the full copyright and license information, please view the LICENSE.txt  */
/*      file that was distributed with this source code.                             */
/*************************************************************************************/

namespace Thelia\Tests\Action;

use Thelia\Action\FeatureAv;
use Thelia\Core\Event\Feature\FeatureAvCreateEvent;
use Thelia\Core\Event\Feature\FeatureAvDeleteEvent;
use Thelia\Core\Event\Feature\FeatureAvUpdateEvent;
use Thelia\Model\FeatureAv as FeatureAvModel;
use Thelia\Model\FeatureQuery;

/**
 * Class FeatureAvTest
 * @package Thelia\Tests\Action
 * @author Manuel Raynaud <mraynaud@openstudio.fr>
 */
class FeatureAvTest extends \PHPUnit_Framework_TestCase
{
    protected $dispatcher;

    public function setUp()
    {
        $this->dispatcher = $this->getMock("Symfony\Component\EventDispatcher\EventDispatcherInterface");
    }

    /**
     * @return \Thelia\Model\Feature
     */
    protected function getRandomFeature()
    {
        $feature = FeatureQuery::create()
            ->addAscendingOrderByColumn('RAND()')
            ->findOne();

        if (null === $feature) {
            $this->fail('use fixtures before launching test, there is no feature in database');
        }

        return $feature;
    }

    public function testCreate()
    {
        $feature = $this->getRandomFeature();

        $event = new FeatureAvCreateEvent();
        $event
            ->setFeatureId($feature->getId())
            ->setLocale('en_US')
            ->setTitle('test')
            ->setDispatcher($this->dispatcher);

        $action = new FeatureAv();
        $action->create($event);

        $createdFeatureAv = $event->getFeatureAv();

        $this->assertInstanceOf('Thelia\Model\FeatureAv', $createdFeatureAv);

        $this->assertFalse($createdFeatureAv->isNew());

        $this->assertEquals('en_US', $createdFeatureAv->getLocale());
        $this->assertEquals('test', $createdFeatureAv->getTitle());
        $this->assertEquals($feature->getId(), $createdFeatureAv->getFeatureId());

        return $createdFeatureAv;
    }

    /**
     * @param FeatureAvModel $featureAv
     * @depends testCreate
     */
    public function testUpdate(FeatureAvModel $featureAv)
    {
        $event = new FeatureAvUpdateEvent($featureAv->getId());
        $event
            ->setLocale('en_uS')
            ->setTitle('test update')
            ->setDescription('test description')
            ->setChapo('test chapo')
            ->setPostscriptum('test postscriptum')
            ->setDispatcher($this->dispatcher);

        $action = new FeatureAv();
        $action->update($event);

        $updatedFeatureAv = $event->getFeatureAv();

        $this->assertInstanceOf('Thelia\Model\FeatureAv', $updatedFeatureAv);

        $this->assertEquals('en_US', $updatedFeatureAv->getLocale());
        $this->assertEquals('test update', $updatedFeatureAv->getTitle());
        $this->assertEquals('test chapo', $updatedFeatureAv->getChapo());
        $this->assertEquals('test description', $updatedFeatureAv->getDescription());
        $this->assertEquals('test postscriptum', $updatedFeatureAv->getPostscriptum());

        return $updatedFeatureAv;
    }

    /**
     * @param FeatureAvModel $featureAv
     * @depends testUpdate
     */
    public function testDelete(FeatureAvModel $featureAv)
    {
        $event = new FeatureAvDeleteEvent($featureAv->getId());
        $event->setDispatcher($this->dispatcher);

        $action = new FeatureAv();
        $action->delete($event);

        $deletedFeatureAv = $event->getFeatureAv();

        $this->assertInstanceOf('Thelia\Model\FeatureAv', $deletedFeatureAv);

        $this->assertTrue($deletedFeatureAv->isDeleted());
    }
}
