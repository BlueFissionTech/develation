<?php

namespace BlueFission\Tests\Prototypes;

use BlueFission\Behavioral\Behaviors\Meta;
use BlueFission\Obj;
use BlueFission\Prototypes\Agent;
use BlueFission\Prototypes\Artifact;
use BlueFission\Prototypes\Blueprint;
use BlueFission\Prototypes\Collective;
use BlueFission\Prototypes\Domain;
use BlueFission\Prototypes\Entity;
use BlueFission\Prototypes\HasCollectives;
use BlueFission\Prototypes\HasConditions;
use BlueFission\Prototypes\IsCausal;
use BlueFission\Prototypes\Position;
use BlueFission\Prototypes\Proto;
use PHPUnit\Framework\TestCase;

final class PrototypeSubstrateTest extends TestCase
{
    public function testProtoSnapshotCapturesCoreMetadata(): void
    {
        $object = new PrototypeFixture();
        $object->protoId('dog_01')
            ->name('Scout')
            ->kind('entity')
            ->addLabel('dog')
            ->addTrait('animal')
            ->property('color', 'brown')
            ->measure('speed', 4)
            ->confidence(0.75)
            ->record('observed', ['by' => 'camera']);

        $snapshot = $object->snapshot();

        $this->assertSame('dog_01', $snapshot['id']);
        $this->assertSame('Scout', $snapshot['name']);
        $this->assertSame('entity', $snapshot['kind']);
        $this->assertSame('brown', $snapshot['properties']['color']);
        $this->assertSame(4, $snapshot['measures']['speed']);
        $this->assertCount(1, $snapshot['history']);
    }

    public function testBlueprintArtifactEntityAndAgentExposeDistinctMetadata(): void
    {
        $blueprint = new BlueprintFixture();
        $blueprint->protoId('bp.dog')
            ->name('Dog Blueprint')
            ->archetype('animal')
            ->capability('move', 1)
            ->addConstraint('requires_food', true)
            ->component('tail', ['count' => 1]);

        $artifact = new ArtifactFixture();
        $artifact->protoId('artifact.dog.bowl')
            ->blueprint($blueprint)
            ->substance('steel')
            ->materiality('substantial');

        $entity = new EntityFixture();
        $entity->protoId('entity.dog.scout')
            ->name('Scout')
            ->reactive(true)
            ->property('species', 'dog');

        $agent = new AgentFixture();
        $agent->protoId('agent.handler')
            ->name('Handler')
            ->role('operator')
            ->scope('local')
            ->awareness('room')
            ->efficacy('can_act')
            ->autonomy('externally_controlled')
            ->addGoal('Feed dog')
            ->addCriterion('safety', 5, 'avoid harm')
            ->addStrategy('open_door', 2, 'exit')
            ->decide(['recommendation' => 'open kitchen door']);

        $this->assertSame('animal', $blueprint->archetype());
        $this->assertSame('steel', $artifact->substance());
        $this->assertSame('dog', $entity->property('species'));
        $this->assertSame('operator', $agent->role());
        $this->assertCount(1, $agent->goals());
        $this->assertSame('open kitchen door', $agent->snapshot()['lastDecision']['recommendation']);
    }

    public function testPositionTraitSupportsCoordinatesAndDistances(): void
    {
        $left = new PositionedFixture();
        $right = new PositionedFixture();

        $left->defineDimension('x', ['kind' => 'spatial', 'absolute' => true])
            ->defineDimension('y', ['kind' => 'spatial', 'absolute' => true])
            ->coordinate('x', 0)
            ->coordinate('y', 0);

        $right->defineDimension('x', ['kind' => 'spatial', 'absolute' => true])
            ->defineDimension('y', ['kind' => 'spatial', 'absolute' => true])
            ->coordinate('x', 3)
            ->coordinate('y', 4);

        $this->assertSame(5.0, $left->distanceTo($right));
    }

    public function testConditionsAndCausalityCanFilterCandidateCauses(): void
    {
        $entity = new ConditionalEntityFixture();
        $entity->protoId('entity.dog.scout')
            ->name('Scout')
            ->addCondition([
                'name' => 'hungry',
                'path' => 'states.hungry',
                'expected' => true,
                'operator' => 'equals',
            ])
            ->addCause([
                'name' => 'entered_for_food',
                'weight' => 0.9,
                'conditions' => [[
                    'name' => 'food_smell',
                    'path' => 'signals.food_smell',
                    'expected' => true,
                    'operator' => 'equals',
                ]],
            ])
            ->addCause([
                'name' => 'entered_randomly',
                'weight' => 0.2,
            ]);

        $context = [
            'states' => ['hungry' => true],
            'signals' => ['food_smell' => true],
        ];

        $this->assertTrue($entity->conditionsMet($context));

        $causes = $entity->inferCauses($context);

        $this->assertSame('entered_for_food', $causes[0]['name']);
        $this->assertCount(2, $causes);
    }

    public function testDomainOwnsMembersSubdomainsAndCollectives(): void
    {
        $house = new DomainFixture();
        $house->protoId('domain.house')
            ->domainName('House')
            ->rule('entry_requires_opening', true)
            ->defaultValue('food_source', 'kitchen')
            ->domainState('occupied', true);

        $room = new DomainFixture();
        $room->protoId('domain.house.kitchen')->domainName('Kitchen');

        $dog = new EntityFixture();
        $dog->protoId('entity.dog.scout')->name('Scout');

        $pack = new CollectiveFixture();
        $pack->protoId('collective.pack')->name('Pack')->collectiveKind('pack')->sharedDestiny('stay_together');
        $pack->addMember($dog, 'scout');

        $house->addSubdomain($room, 'kitchen');
        $house->addMember($dog, 'scout');
        $house->addCollective($pack, 'pack');

        $snapshot = $house->snapshot();

        $this->assertSame('House', $snapshot['name']);
        $this->assertCount(1, $snapshot['domain_members']);
        $this->assertCount(1, $snapshot['domain_subdomains']);
        $this->assertCount(1, $snapshot['domain_collectives']);
        $this->assertSame('kitchen', $snapshot['domain_defaults']['food_source']);
    }

    public function testCollectiveMembershipCanBeTrackedOnEntities(): void
    {
        $dog = new SocialEntityFixture();
        $dog->protoId('entity.dog.scout')->name('Scout');

        $flock = [
            'id' => 'collective.flock',
            'name' => 'Flock',
        ];

        $dog->joinCollective($flock, ['role' => 'member']);

        $this->assertTrue($dog->inCollective('collective.flock'));
        $this->assertCount(1, $dog->collectives());
    }

    public function testBehavioralCarrierDispatchesPrototypeEvents(): void
    {
        $entity = new PrototypeFixture();
        $called = false;

        $entity->when('prototypes.proto.property_changed', function ($behavior, $args) use (&$called): void {
            $called = $args instanceof Meta;
        });

        $entity->property('species', 'dog');

        $this->assertTrue($called);
    }
}

final class PrototypeFixture extends Obj
{
    use Proto;
}

final class BlueprintFixture extends Obj
{
    use Proto;
    use Blueprint;
}

final class ArtifactFixture extends Obj
{
    use Proto;
    use Artifact;
}

final class EntityFixture extends Obj
{
    use Proto;
    use Artifact;
    use Entity;
}

final class AgentFixture extends Obj
{
    use Proto;
    use Artifact;
    use Entity;
    use Agent;
}

final class PositionedFixture extends Obj
{
    use Proto;
    use Position;
}

final class ConditionalEntityFixture extends Obj
{
    use Proto;
    use Entity;
    use HasConditions;
    use IsCausal;
}

final class DomainFixture extends Obj
{
    use Proto;
    use Domain;
}

final class CollectiveFixture extends Obj
{
    use Proto;
    use Collective;
}

final class SocialEntityFixture extends Obj
{
    use Proto;
    use Entity;
    use HasCollectives;
}
