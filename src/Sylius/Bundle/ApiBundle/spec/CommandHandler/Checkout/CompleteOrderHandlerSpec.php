<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace spec\Sylius\Bundle\ApiBundle\CommandHandler\Checkout;

use PhpSpec\ObjectBehavior;
use SM\Factory\FactoryInterface;
use SM\StateMachine\StateMachineInterface;
use Sylius\Bundle\ApiBundle\Command\Checkout\CompleteOrder;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\OrderCheckoutTransitions;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;

final class CompleteOrderHandlerSpec extends ObjectBehavior
{
    function let(OrderRepositoryInterface $orderRepository, FactoryInterface $stateMachineFactory): void
    {
        $this->beConstructedWith($orderRepository, $stateMachineFactory);
    }

    function it_handles_order_completion_without_notes(
        OrderRepositoryInterface $orderRepository,
        StateMachineInterface $stateMachine,
        OrderInterface $order,
        FactoryInterface $stateMachineFactory
    ): void {
        $completeOrder = new CompleteOrder();
        $completeOrder->setOrderTokenValue('ORDERTOKEN');

        $orderRepository->findOneBy(['tokenValue' => 'ORDERTOKEN'])->willReturn($order);

        $order->setNotes(null)->shouldNotBeCalled();

        $stateMachineFactory->get($order, OrderCheckoutTransitions::GRAPH)->willReturn($stateMachine);
        $stateMachine->can('complete')->willReturn(true);
        $order->getTokenValue()->willReturn('COMPLETED_ORDER_TOKEN');

        $stateMachine->apply('complete')->shouldBeCalled();

        $this($completeOrder)->shouldReturn($order);
    }

    function it_handles_order_completion_with_notes(
        OrderRepositoryInterface $orderRepository,
        StateMachineInterface $stateMachine,
        OrderInterface $order,
        FactoryInterface $stateMachineFactory
    ): void {
        $completeOrder = new CompleteOrder('ThankYou');
        $completeOrder->setOrderTokenValue('ORDERTOKEN');

        $orderRepository->findOneBy(['tokenValue' => 'ORDERTOKEN'])->willReturn($order);

        $order->setNotes('ThankYou')->shouldBeCalled();

        $stateMachineFactory->get($order, OrderCheckoutTransitions::GRAPH)->willReturn($stateMachine);
        $stateMachine->can('complete')->willReturn(true);
        $order->getTokenValue()->willReturn('COMPLETED_ORDER_TOKEN');

        $stateMachine->apply('complete')->shouldBeCalled();

        $this($completeOrder)->shouldReturn($order);
    }

    function it_throws_an_exception_if_order_does_not_exist(
        OrderRepositoryInterface $orderRepository
    ): void {
        $completeOrder = new CompleteOrder();
        $completeOrder->setOrderTokenValue('ORDERTOKEN');

        $orderRepository->findOneBy(['tokenValue' => 'ORDERTOKEN'])->willReturn(null);

        $this
            ->shouldThrow(\InvalidArgumentException::class)
            ->during('__invoke', [$completeOrder])
        ;
    }

    function it_throws_an_exception_if_order_cannot_be_completed(
        OrderRepositoryInterface $orderRepository,
        StateMachineInterface $stateMachine,
        OrderInterface $order,
        FactoryInterface $stateMachineFactory
    ): void {
        $completeOrder = new CompleteOrder();
        $completeOrder->setOrderTokenValue('ORDERTOKEN');

        $orderRepository->findOneBy(['tokenValue' => 'ORDERTOKEN'])->willReturn($order);

        $stateMachineFactory->get($order, OrderCheckoutTransitions::GRAPH)->willReturn($stateMachine);
        $stateMachine->can(OrderCheckoutTransitions::TRANSITION_COMPLETE)->willReturn(false);

        $this
            ->shouldThrow(\InvalidArgumentException::class)
            ->during('__invoke', [$completeOrder])
        ;
    }
}
