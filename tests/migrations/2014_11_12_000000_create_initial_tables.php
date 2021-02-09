<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Faker\Factory as Faker;

use ROTGP\RestEasy\Test\Models\Genre;
use ROTGP\RestEasy\Test\Models\Role;
use ROTGP\RestEasy\Test\Models\StreamingService;
use ROTGP\RestEasy\Test\Models\RecordLabel;
use ROTGP\RestEasy\Test\Models\Artist;
use ROTGP\RestEasy\Test\Models\Album;
use ROTGP\RestEasy\Test\Models\Song;
use ROTGP\RestEasy\Test\Models\User;
use ROTGP\RestEasy\Test\Models\Play;

class CreateInitialTables extends Migration
{
    private static $fake;

    public static function faker() {
        if (!isset(self::$fake)) self::$fake = Faker::create();
        return self::$fake;
    }
    
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('genres', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
        });
        $this->createConstant(Genre::class);

        Schema::create('record_labels', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
        });
        $this->createConstant(RecordLabel::class);

        Schema::create('streaming_services', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
        });
        $this->createConstant(StreamingService::class);

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
        });
        $this->createConstant(Role::class);

        Schema::create('artists', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('biography', 500)->unique();
            $table->foreignId('record_label_id')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->string('fan_mail_address', 500)->nullable();
            $table->string('history')->default('');
            $table->timestamps();
        });

        Schema::create('albums', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->foreignId('artist_id')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('genre_id')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->dateTime('release_date');
            $table->double('price', 8, 2)->default(0);
            $table->unsignedBigInteger('purchases')->default(0);
            $table->timestamps();
        });

        Schema::create('songs', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->foreignId('album_id')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->integer('length_seconds');
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->default(Role::FAN)->constrained()->onUpdate('cascade')->onDelete('cascade');
        });

        Schema::create('artist_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('artist_id')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->nullableTimestamps();
        });

        Schema::create('album_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('album_id')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->nullableTimestamps();
        });

        Schema::create('song_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('song_id')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->nullableTimestamps();
        });

        Schema::create('plays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('song_id')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('streaming_service_id')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->datetime('listen_time');
            $table->timestamps();
        });

        $this->seed();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('plays');
        Schema::dropIfExists('song_user');
        Schema::dropIfExists('album_user');
        Schema::dropIfExists('artist_user');
        Schema::dropIfExists('users');
        Schema::dropIfExists('songs');
        Schema::dropIfExists('albums');
        Schema::dropIfExists('artists');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('streaming_services');
        Schema::dropIfExists('record_labels');
        Schema::dropIfExists('genres');
    }

    public function createConstant($class) {
        $reflClass = new ReflectionClass($class);
        $constants = array_diff($reflClass->getConstants(),$reflClass->getParentClass()->getConstants());
        $model = [];
        foreach ($constants as $name => $value)
            $model[] = ['id' => $value, 'name' => strtolower($name)];
        DB::table(app($class)->getTable())->insert($model);
    }

    public function seed()
    {
        self::faker()->seed(10);

        $genres = Genre::all()->toArray();
        $recordLabels = RecordLabel::all()->toArray();
        $streamingServices = StreamingService::all()->toArray();
        
        for ($i = 0; $i <= 10; $i++) {
            
            $artist = new Artist([
                'name' => self::faker()->name,
                'biography' => self::faker()->paragraph,
                'record_label_id' => self::faker()->randomElement($recordLabels)['id']
            ]);
            
            if (self::faker()->boolean === true)
                $artist->fan_mail_address = self::faker()->address;
            $artist->save();

            $albumCount = self::faker()->numberBetween(0, 4);
            for ($j = 0; $j <= $albumCount; $j++) {
                $album = new Album([
                    'name' => self::faker()->sentence,
                    'artist_id' => $artist->id,
                    'genre_id' => self::faker()->randomElement($genres)['id'],
                    'release_date' => self::faker()->dateTimeBetween(new DateTime('2000-01-01 00:00'), new DateTime('2020-03-31 00:00'), 'UTC'),
                    'price' => self::faker()->numberBetween(1, 30) + (self::faker()->numberBetween(1, 50) / 100),
                    'purchases' => self::faker()->numberBetween(1, 1000)
                ]);
                $album->save();

                $songCount = self::faker()->numberBetween(2, 6);
                for ($k = 0; $k <= $songCount; $k++) {
                    $song = new Song([
                        'name' => self::faker()->sentence,
                        'album_id' => $album->id,
                        'length_seconds' => self::faker()->numberBetween(3, 300),
                    ]);
                    $song->save();
                }
            }
        }

        $albumIds = Album::all()->pluck('id')->toArray();
        $artistIds = Artist::all()->pluck('id')->toArray();
        $songIds = Song::all()->pluck('id')->toArray();
        $userIds = [];
        $streamingServiceIds = StreamingService::all()->pluck('id')->toArray();

        for ($i = 0; $i <= 10; $i++) {
            $user = new User([
                'name' => self::faker()->userName,
                'email' => self::faker()->email,
                'password' => self::faker()->password
            ]);
            if ($i % 5 === 0) {
                $user->role_id = Role::ADMIN;
            }
            $user->save();
            $userIds[] = $user->id;
 
            $user->artists()->sync(self::faker()->randomElements($artistIds, self::faker()->numberBetween(0, 5)));
            $user->albums()->sync(self::faker()->randomElements($albumIds, self::faker()->numberBetween(0, 5)));
            $user->songs()->sync(self::faker()->randomElements($songIds, self::faker()->numberBetween(0, 10)));
        }

        foreach ($userIds as $userId) {
            $playCount = self::faker()->numberBetween(0, 10);
            for ($i = 0; $i <= $playCount; $i++) {
                $play = new Play([
                    'user_id' => $userId,
                    'song_id' => self::faker()->randomElement($songIds),
                    'streaming_service_id' => self::faker()->randomElement($streamingServiceIds),
                    'listen_time' => self::faker()->dateTimeBetween(new DateTime('2000-01-01 00:00'), new DateTime('2020-03-31 00:00'), 'UTC')
                ]);
                $play->save();
            }
        }
    }
}

