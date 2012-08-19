<?php

namespace React\Tests\Dns\Query;

use React\Dns\Query\CachedExecutor;
use React\Dns\Query\RecordCache;
use React\Dns\Query\Query;
use React\Dns\Model\Message;
use React\Dns\Model\Record;

class CachedExecutorTest extends \PHPUnit_Framework_TestCase
{
    /** @test */
    public function queryShouldDelegateToDecoratedExecutor()
    {
        $executor = $this->createExecutorMock();
        $executor
            ->expects($this->once())
            ->method('query')
            ->with('8.8.8.8', $this->isInstanceOf('React\Dns\Query\Query'), $this->isInstanceOf('Closure'));

        $cache = new RecordCache();
        $cachedExecutor = new CachedExecutor($executor, $cache);

        $query = new Query('igor.io', Message::TYPE_A, Message::CLASS_IN);
        $cachedExecutor->query('8.8.8.8', $query, function () {});
    }

    /** @test */
    public function callingQueryTwiceShouldUseCachedResult()
    {
        $executor = $this->createExecutorMock();
        $executor
            ->expects($this->once())
            ->method('query')
            ->will($this->callQueryCallbackWithAddress('178.79.169.131'));

        $cache = new RecordCache();
        $cachedExecutor = new CachedExecutor($executor, $cache);

        $query = new Query('igor.io', Message::TYPE_A, Message::CLASS_IN);
        $cachedExecutor->query('8.8.8.8', $query, function () {});
        $cachedExecutor->query('8.8.8.8', $query, function () {});
    }

    private function callQueryCallbackWithAddress($address)
    {
        return $this->returnCallback(function ($nameserver, $query, $callback) use ($address) {
            $response = new Message();
            $response->header->set('qr', 1);
            $response->questions[] = new Record($query->name, $query->type, $query->class);
            $response->answers[] = new Record($query->name, $query->type, $query->class, 3600, $address);

            $callback($response);
        });
    }

    protected function expectCallableOnce()
    {
        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke');

        return $mock;
    }

    protected function createCallableMock()
    {
        return $this->getMock('React\Tests\Socket\Stub\CallableStub');
    }

    private function createExecutorMock()
    {
        return $this->getMock('React\Dns\Query\ExecutorInterface');
    }
}
