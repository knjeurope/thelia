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
use Propel\Runtime\ActiveQuery\Criteria;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Thelia\Action\Content;
use Thelia\Core\Event\Content\ContentAddFolderEvent;
use Thelia\Core\Event\Content\ContentCreateEvent;
use Thelia\Core\Event\Content\ContentDeleteEvent;
use Thelia\Core\Event\Content\ContentRemoveFolderEvent;
use Thelia\Core\Event\Content\ContentToggleVisibilityEvent;
use Thelia\Core\Event\Content\ContentUpdateEvent;
use Thelia\Core\Event\UpdatePositionEvent;
use Thelia\Model\ContentFolder;
use Thelia\Model\ContentFolderQuery;
use Thelia\Model\ContentQuery;
use Thelia\Model\FolderQuery;
use Thelia\Tests\TestCaseWithURLToolSetup;

/**
 * Class ContentTest
 * @package Thelia\Tests\Action
 * @author Manuel Raynaud <mraynaud@openstudio.fr>
 */
class ContentTest extends TestCaseWithURLToolSetup
{
    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    public function setUp()
    {
        $this->dispatcher = $this->getMock("Symfony\Component\EventDispatcher\EventDispatcherInterface");
    }

    public function getUpdateEvent(&$content)
    {
        if (!$content instanceof \Thelia\Model\Content) {
            $content = $this->getRandomContent();
        }

        $event = new ContentUpdateEvent($content->getId());
        $event->setDispatcher($this->dispatcher);
        $event
            ->setVisible(1)
            ->setLocale($content->getLocale())
            ->setTitle($content->getTitle())
            ->setChapo($content->getChapo())
            ->setDescription($content->getDescription())
            ->setPostscriptum($content->getPostscriptum())
            ->setDefaultFolder($content->getDefaultFolderId())
        ;

        return $event;
    }

    public function processUpdateAction($event)
    {
        $contentAction = new Content($this->getContainer());
        $contentAction->update($event);

        return $event->getContent();
    }

    public function testCreateContent()
    {
        $folder = $this->getRandomFolder();

        $event = new ContentCreateEvent();
        $event->setDispatcher($this->dispatcher);
        $event
            ->setVisible(1)
            ->setLocale('en_US')
            ->setTitle('test create content')
            ->setDefaultFolder($folder->getId())
        ;

        $contentAction = new Content($this->getContainer());
        $contentAction->create($event);

        $createdContent = $event->getContent();

        $this->assertInstanceOf('Thelia\Model\Content', $createdContent);
        $this->assertEquals(1, $createdContent->getVisible());
        $this->assertEquals('test create content', $createdContent->getTitle());
        $this->assertEquals($folder->getId(), $createdContent->getDefaultFolderId());

    }

    public function testUpdateContent()
    {
        $content = $this->getRandomContent();
        $folder = $this->getRandomFolder();

        $event = new ContentUpdateEvent($content->getId());
        $event->setDispatcher($this->dispatcher);
        $event
            ->setVisible(1)
            ->setLocale('en_US')
            ->setTitle('test update content title')
            ->setChapo('test update content short description')
            ->setDescription('test update content description')
            ->setPostscriptum('test update content postscriptum')
            ->setDefaultFolder($folder->getId())
        ;

        $contentAction = new Content($this->getContainer());
        $contentAction->update($event);

        $updatedContent = $event->getContent();

        $this->assertInstanceOf('Thelia\Model\Content', $updatedContent);
        $this->assertEquals(1, $updatedContent->getVisible());
        $this->assertEquals('test update content title', $updatedContent->getTitle());
        $this->assertEquals('test update content short description', $updatedContent->getChapo());
        $this->assertEquals('test update content description', $updatedContent->getDescription());
        $this->assertEquals('test update content postscriptum', $updatedContent->getPostscriptum());
        $this->assertEquals($folder->getId(), $updatedContent->getDefaultFolderId());
    }

    public function testDeleteContent()
    {
        $content = $this->getRandomContent();

        $event = new ContentDeleteEvent($content->getId());
        $event->setDispatcher($this->dispatcher);

        $contentAction = new Content($this->getContainer());
        $contentAction->delete($event);

        $deletedContent = $event->getContent();

        $this->assertInstanceOf('Thelia\Model\Content', $deletedContent);
        $this->assertTrue($deletedContent->isDeleted());

    }

    public function testContentToggleVisibility()
    {
        $content = $this->getRandomContent();

        $visibility = $content->getVisible();

        $event = new ContentToggleVisibilityEvent($content);
        $event->setDispatcher($this->dispatcher);

        $contentAction = new Content($this->getContainer());
        $contentAction->toggleVisibility($event);

        $updatedContent = $event->getContent();

        $this->assertInstanceOf('Thelia\Model\Content', $updatedContent);
        $this->assertEquals(!$visibility, $updatedContent->getVisible());
    }

    public function testUpdatePositionUp()
    {
        $content = ContentQuery::create()
            ->filterByPosition(1, Criteria::GREATER_THAN)
            ->findOne();

        if (null === $content) {
            $this->fail('use fixtures before launching test, there is no content in database');
        }

        $newPosition = $content->getPosition()-1;

        $event = new UpdatePositionEvent($content->getId(), UpdatePositionEvent::POSITION_UP);
        $event->setDispatcher($this->dispatcher);

        $contentAction = new Content($this->getContainer());
        $contentAction->updatePosition($event);

        $updatedContent = ContentQuery::create()->findPk($content->getId());

        $this->assertEquals($newPosition, $updatedContent->getPosition(),sprintf("new position is %d, new position expected is %d for content %d", $newPosition, $updatedContent->getPosition(), $updatedContent->getId()));
    }

    public function testUpdatePositionDown()
    {
        $content = ContentQuery::create()
            ->filterByPosition(1)
            ->findOne();

        if (null === $content) {
            $this->fail('use fixtures before launching test, there is no content in database');
        }

        $newPosition = $content->getPosition()+1;

        $event = new UpdatePositionEvent($content->getId(), UpdatePositionEvent::POSITION_DOWN);
        $event->setDispatcher($this->dispatcher);

        $contentAction = new Content($this->getContainer());
        $contentAction->updatePosition($event);

        $updatedContent = ContentQuery::create()->findPk($content->getId());

        $this->assertEquals($newPosition, $updatedContent->getPosition(),sprintf("new position is %d, new position expected is %d for content %d", $newPosition, $updatedContent->getPosition(), $updatedContent->getId()));
    }

    public function testUpdatePositionWithSpecificPosition()
    {
        $content = ContentQuery::create()
            ->filterByPosition(1, Criteria::GREATER_THAN)
            ->findOne();

        if (null === $content) {
            $this->fail('use fixtures before launching test, there is no content in database');
        }

        $event = new UpdatePositionEvent($content->getId(), UpdatePositionEvent::POSITION_ABSOLUTE, 1);
        $event->setDispatcher($this->dispatcher);

        $contentAction = new Content($this->getContainer());
        $contentAction->updatePosition($event);

        $updatedContent = ContentQuery::create()->findPk($content->getId());

        $this->assertEquals(1, $updatedContent->getPosition(),sprintf("new position is 1, new position expected is %d for content %d", $updatedContent->getPosition(), $updatedContent->getId()));
    }

    public function testAddFolderToContent()
    {
        $content = $this->getRandomContent();

        do {
            $folder = $this->getRandomFolder();

            $test = ContentFolderQuery::create()
                ->filterByContent($content)
                ->filterByFolder($folder);
        } while ($test->count() > 0);

        $event = new ContentAddFolderEvent($content, $folder->getId());
        $event->setDispatcher($this->dispatcher);

        $contentAction = new Content($this->getContainer());
        $contentAction->addFolder($event);

        $testAddFolder = ContentFolderQuery::create()
            ->filterByContent($content)
            ->filterByFolder($folder)
            ->findOne()
        ;

        $this->assertNotNull($testAddFolder);
        $this->assertEquals($content->getId(), $testAddFolder->getContentId(), 'check if content id are equals');
        $this->assertEquals($folder->getId(), $testAddFolder->getFolderId(), 'check if folder id are equals');

        return $testAddFolder;
    }

    /**
     * @depends testAddFolderToContent
     */
    public function testRemoveFolder(ContentFolder $association)
    {
        $event = new ContentRemoveFolderEvent($association->getContent(), $association->getFolder()->getId());
        $event->setDispatcher($this->dispatcher);

        $contentAction = new Content($this->getContainer());
        $contentAction->removeFolder($event);

        $testAssociation = ContentFolderQuery::create()
            ->filterByContent($association->getContent())
            ->filterByFolder($association->getFolder())
            ->findOne();

        $this->assertNull($testAssociation);
    }
    /**
     * @return \Thelia\Model\Content
     */
    protected function getRandomContent()
    {
        $content = ContentQuery::create()
            ->addAscendingOrderByColumn('RAND()')
            ->findOne();

        if (null === $content) {
            $this->fail('use fixtures before launching test, there is no content in database');
        }

        return $content;
    }

    /**
     * @return \Thelia\Model\Folder
     */
    protected function getRandomFolder()
    {
        $folder = FolderQuery::create()
            ->addAscendingOrderByColumn('RAND()')
            ->findOne();

        if (null === $folder) {
            $this->fail('use fixtures before launching test, there is no folder in database');
        }

        return $folder;
    }
}
