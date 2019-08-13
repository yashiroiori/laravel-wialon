<?php
/**
 * Created by PhpStorm.
 * User: ps
 * Date: 19/10/18
 * Time: 02:10 PM
 */

namespace Punksolid\Wialon\Tests;

use Orchestra\Testbench\TestCase;
use Punksolid\Wialon\ControlType;
use Punksolid\Wialon\Geofence;
use Punksolid\Wialon\GeofenceControlType;
use Punksolid\Wialon\PanicButtonControlType;
use Punksolid\Wialon\Notification;
use Punksolid\Wialon\NotificationType;
use Punksolid\Wialon\Resource;
use Punksolid\Wialon\Unit;
use Punksolid\Wialon\Wialon;
use Punksolid\Wialon\SensorControlType;

class NotificationTest extends TestCase
{
    /**
     * @return array
     * @throws \Punksolid\Wialon\WialonErrorException
     */
    public function getBasics(): array
    {
        $units = Unit::all()->take(2);
        $resource = Resource::findByName('punksolid@twitter.com');
        //        $resource = Resource::all()->first(); //didn't work, maybe exists some rule about creating a notification with incompatible resources
        if ($resource) {
            dump("encontró resource");
        }
        return array($units, $resource);
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('services.wialon.token', '5dce19710a5e26ab8b7b8986cb3c49e58C291791B7F0A7AEB8AFBFCEED7DC03BC48FF5F8'); // wialon playground token
    }

    protected function setUp(): void
    {
        parent::setUp(); // TODO: Change the autogenerated stub


    }


    public function test_update_txt_notification()
    {
        $notifications = Notification::all();
        $notification_to_modify = $notifications->first();
        $notification_to_modify->txt = "123ABC";
        $notification_to_modify->update();

        $this->assertEquals("123ABC", $notification_to_modify->txt);
    }

    public function test_list_notifications()
    {
        $notifications = Notification::all();
        // Attributes especific to notificationsSdkDemo
        $this->assertObjectHasAttribute("id", $notifications->first(), "Unit has id");
        $this->assertObjectHasAttribute("name", $notifications->first(), "Unit has measure units");
        $this->assertObjectHasAttribute("nm", $notifications->first(), "Unit has measure units");
        $this->assertObjectHasAttribute("control_type", $notifications->first(), "Unit has name");
        $this->assertObjectHasAttribute("actions", $notifications->first(), "Unit has  superclass ID: avl_unit");
        $this->assertObjectHasAttribute("text", $notifications->first(), "Unit has uacl current user access level for unit");
        $this->assertObjectHasAttribute("resource", $notifications->first(), "Unit has uacl current user access level for unit");

    }

    public function test_find_notification_by_resource_and_id_underscored()
    {

        $searched_notification = Notification::all()->first();
        $found_notification = Notification::findByUniqueId("{$searched_notification->resource->id}_{$searched_notification->id}");
        $this->assertEquals($searched_notification->name, $found_notification->name);
    }

    public function test_create_notification_by_speed_fixed_speed_limit()
    {
        list($units, $resource) = $this->getBasics();
        $min_speed = 0;
        $max_speed = 60;
        $control_type = new ControlType('speed', [
            'max_speed' => $max_speed,
            'min_speed' => $min_speed
        ]);

        $notification = Notification::make(
            $resource,
            $units,
            $control_type,
            "Velocity Check"
        );

        $this->assertEquals("Velocity Check", $notification->name);
        $this->assertEquals($resource->id."_".$notification->id,$notification->unique_id);
    }

    public function test_create_notification_by_SOS_panic_button()
    {
        list($units, $resource) = $this->getBasics();

        //        $control_type2 = new ControlType('panic_button');

        $control_type = new PanicButtonControlType();
        $action = new Notification\Action("push_messages", [
            "url" => "http://api.dogoit.com/api/v1/"
        ]);
        $notification = Notification::make(
            $resource,
            $units,
            $control_type,
            "PanicButton",
            $action
        );

        $this->assertEquals("PanicButton", $notification->n);
    }

    public function test_param_message_to_hook()
    {
        list($units, $resource) = $this->getBasics();

        $control_type = new ControlType('panic_button');
        $notification = Notification::make(
            $resource,
            $units,
            $control_type,
            "PanicButton"
        );

        $this->assertEquals("this is the message", $notification->txt);
    }

    public function test_create_notification_by_parameter_in_message()
    { }

    public function test_create_notification_by_connection_loss()
    { }

    public function test_create_notification_by_SMS()
    { }

    public function test_create_notification_by_address()
    { }

    public function test_create_notification_by_fuel_filling()
    { }

    public function test_create_notification_by_driver()
    { }

    public function test_create_notification_by_passenger_alarm()
    { }

    public function test_create_notification_by_enter_geofence()
    {
        list($units, $resource) = $this->getBasics();

        $geofence = Geofence::findByName("my_geofence");

        $control_type = new GeofenceControlType($geofence);

        $notification = Notification::make(
            $resource,
            $units,
            $control_type,
            "MiNotificacion101"
        );

        $this->assertEquals("MiNotificacion101", $notification->n);
    }

    public function test_create_notification_by_going_outside_geofence()
    {
        list($units, $resource) = $this->getBasics();

        $geofence = Geofence::findByName("my_geofence");

        $control_type = new GeofenceControlType($geofence);
        $control_type->setType(1); // leaving

        $notification = Notification::make(
            $resource,
            $units,
            $control_type,
            "MiNotificacion102"
        );

        $this->assertEquals("MiNotificacion102", $notification->n);
        $this->assertEquals("1", $notification->trg_p->type);
    }


    public function test_create_notification_by_digital_input()
    { }

    public function test_create_notification_by_sensor_value()
    {
        list($units, $resource) = $this->getBasics();

        $control_type = new SensorControlType(); // defaults
        
        $notification = Notification::make(
            $resource,
            $units,
            $control_type,
            "MiNotificacion103"
        );

        $this->assertEquals("MiNotificacion103", $notification->n);
        $this->assertEquals("0", $notification->trg_p->type);
    }

    public function test_create_SOS_notification_with_webhook_trigger_appointing_dinamically()
    {
        $url = 'http://7b5d47c9.ngrok.io/api/v1/webhook/alert'; // change it everytime that will be tested

        list($units, $resource) = $this->getBasics();
        $control_type = new ControlType('panic_button');
        $action = new Notification\Action('push_messages', [
            "url" => 'https://7b5d47c9.ngrok.io'
        ]);

        $notification = Notification::make($resource, $units, $control_type, 'SOS_wialon', $action);

        $this->assertEquals("SOS_wialon", $notification->name);
    }

    public function test_destroy_notification()
    {
        $this->markAsRisky("Delete Element");
        $this->markTestSkipped("Shouldnt test on production");
        $notification = Notification::all()->last();
        $this->assertTrue($notification->destroy());
    }
}
