<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SubmitLinksTest extends TestCase
{
    use RefreshDatabase;


    /** @test */
    function guest_can_submit_a_new_link()
    {
        $response = $this->post('/submit', [
            'title' => 'Example Title',
            'url' => 'http://example.com',
            'description' => 'Example description.',
        ]);
        
        $this->assertDatabaseHas('links', [
            'title' => 'Example Title'
        ]);

        $response
            ->assertStatus(302)
            ->assertHeader('Location', url('/'));

        $this
            ->get('/')
            ->assertSee('Example Title');
    }

    /** @test */
    function link_not_created_on_validation_fail(){
        $response = $this->post('/submit');

        $response->assertSessionHasErrors(['title', 'url', 'description']);
    }
    
    /** @test */
    function link_not_created_on_invalid_url(){
       
        $this->withoutExceptionHandling();
        
            $cases = ['//invalid-url.com', '/invalid-url', 'foo.com'];
        
            foreach ($cases as $case) {
                try {
                    $response = $this->post('/submit', [
                        'title' => 'Example Title',
                        'url' => $case,
                        'description' => 'Example description',
                    ]);
                } catch (ValidationException $e) {
                    $this->assertEquals(
                        'The url format is invalid.',
                        $e->validator->errors()->first('url')
                    );
                    continue;
                }
        
                $this->fail("The URL $case passed validation when it should have failed.");
            }
    }
    
    /** @test */
    function max_length_fails_over_max(){
        $this->withoutExceptionHandling();
        
            $title = str_repeat('a', 256);
            $description = str_repeat('a', 256);
            $url = 'http://';
            $url .= str_repeat('a', 256 - strlen($url));
        
            try {
                $this->post('/submit', compact('title', 'url', 'description'));
            } catch(ValidationException $e) {
                $this->assertEquals(
                    'The title may not be greater than 255 characters.',
                    $e->validator->errors()->first('title')
                );
        
                $this->assertEquals(
                    'The url may not be greater than 255 characters.',
                    $e->validator->errors()->first('url')
                );
        
                $this->assertEquals(
                    'The description may not be greater than 255 characters.',
                    $e->validator->errors()->first('description')
                );
        
                return;
            }
        
            $this->fail('Max length should trigger a ValidationException');
    }
    /** @test */
    function max_length_succeeds_under_max(){

    }
    
}