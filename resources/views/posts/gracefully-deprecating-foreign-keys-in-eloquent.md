<?php

use TiMacDonald\Website\Format;
use TiMacDonald\Website\Page;

/**
 * Props.
 *
 * @var string $projectBase
 * @var \TiMacDonald\Website\Request $request
 * @var \TiMacDonald\Website\Url $url
 * @var (callable(string): void) $e
 * @var \TiMacDonald\Website\Markdown $markdown
 * @var \TiMacDonald\Website\Collection $collection
 */

// ...

$page = Page::fromPost(
    file: __FILE__,
    title: 'Gracefully deprecating foreign keys for a polymorphic relationship',
    description: 'A "note to self" on how to best handle moving from a classic foreign key constrained relationship, to a polymorphic relationship in Eloquent, without impacting the end user.',
    date: new DateTimeImmutable('@1602311392', new DateTimeZone('Australia/Melbourne')),
    image: $url->asset('deprecate-keys.png'),
);

?>

When migrating a classic foreign key constrained relationship, such as `belongsTo`, over to a polymorphic relationship, you've got to be very sure you've covered all places that the previous foreign key was accessed or set. This might include property access `$model->foreign_key` or other types of access, like the `only` helper; e.g., `$model->only(['foreign_key'])` and also incoming request payloads `Model::create($request->validated())`.

It is common that you will have several `belongsTo` relationships that all share the same foreign key name as well. Just look at your app and tell me how many models `belongsTo` the `User` model? I'm going to hazard a guess and say...a fair few. Which means you've probably referenced `user_id` throughout your app in a number of places in relation to a handful of different models.

I recently migrated a `belongsTo` relationship to a polymorhic `morphTo` relationship, and this blog post is essentially a "note to self" for next time I do this refactor. I'm going to note just a few steps I'd recommend and why.

I'm not going to cover migrating references to the attributes in your app. I'm assuming you've already attempted to update the attribute references everywhere you can.

## Some models

To kick off, we need some models to talk about throughout the post. Let's go with an `Image` and a `BlogPost` as the models we are changing the relationship between.

In our example application we have an `Image` and currently the image `belongsTo` a `BlogPost` as a "Featured image". You know the big images people often put at the top of their blog posts? Yea, those.

Our models currently look something like this...

```php
class BlogPost extends Model
{
    public function image(): HasOne
    {
        return $this->hasOne(Image::class);
    }
}

/**
 * @property int blog_post_id
 */
class Image extends Model
{
    public function blogPost(): BelongsTo
    {
        return $this->belongsTo(BlogPost::class);
    }
}
```

You can see that our `Image` class has an attribute `blog_post_id` which is our foreign key. Nothing too exciting going on here just yet.

In our application we also have a handful of *other* models that also `belongsTo` a `BlogPost`; for example, we also have a `Comment` that `belongsTo` a `BlogPost`, and these other models all share one thing: they all have the `blog_post_id` attribute.

Due to the current state of static analysis in the codebase, we cannot rely on tooling to make this migration for us. There is going to be some manual work, and thus, potential for human error (although even tooling is built by humans, so it's crossed fingers all the way down even with automated tooling). I'm also sure our test suite doesn't cover _every_ possible use of the attributes, so I don't wanna put all my trust in that either.

## The database migration

We already have a lot of data in our database, so we need to migrate the existing foreign key data into our new polymorphic columns. We also want our morph columns to be non-nullable, which means we need to create the columns, populate their data, and finally enforce a non-null constraint on the columns.

```php
public function up(): void
{
    Schema::table('images', function (Blueprint $table): void {
        $table->nullableUuidMorphs('imageable');
    });

    DB::table('images')->eachById(function (object $image): void {
        DB::table('images')
            ->where('id', '=', $image->id)
            ->update([
                'imageable_id' => $image->blog_post_id,
                'imageable_type' => BlogPost::class,
            ]);
    });

    Schema::table('images', function (Blueprint $table): void {
        $table->dropColumn('blog_post_id');

        $table->uuid('imageable_id')->nullable(false)->change();
        $table->string('imageable_type')->nullable(false)->change();
    });
}
```

Something to note here is that we reached for the `DB` facade instead of the eloquent model. We could have done something along these lines instead...

```php
// create nullable morph column

Image::eachById(fn ($image) => $image->update([
    'imageable_id' => $image->blog_post_id,
    'imageable_type' => BlogPost::class,
]));

// make morph column non-nullable
```

This *looks* nicer, but our migration is now tied to our model's implementation throughout time. What happens, for example, when we decide to delete our `Image` model and migrate to Spatie's media library? Now we have got to go and change our migrations, which if you mess up and don't refactor to be exactly the same as the original model based version is going to give you headaches. Migrations should be immutable.

Sure, the `DB` contract may also change over time, but I believe it is going to be much more stable than our model. Also we are not touching any undocumented dark corner methods. I very much doubt these methods are going anywhere.

## The relationship migration

We need to migrate this `belongsTo` relation on the model to a polymorphic `morphTo` relation. The model refactor is relatively painless...

```php
class BlogPost extends Model
{
    public function image(): MorphOne
    {
        return $this->morphOne(Image::class, 'imageable');
    }
}

/**
 * @property int imageable_id
 * @property string imageable_type
 */
class Image extends Model
{
    public function imageable(): MorphTo
    {
        return $this->morphTo();
    }
}
```

This is just switching to Laravel's convention for morph models. For more information on this, you can checkout the [Laravel docs](https://laravel.com/docs/8.x/eloquent-relationships#polymorphic-relationships).

## Catching references to the deprecated attribute

All going well, doing the above steps will mean we have made the migration successfully, but we don't want to leave it to chance that we have found all the attribute references.

If we do try and access the old `blog_post_id` attribute on the `Image` model, it is going to silently fail. Eloquent will return `null` when we access attributes that do not exist.

```php
$image->blog_post_id;
//> null

$image->some_other_key_that_doesnt_exist;
//> null
```

To remedy this for our current application, we are going to create an [accessor and a mutator](https://laravel.com/docs/8.x/eloquent-mutators#introduction) to help catch any remaining references. This allows us to gracefully deprecate the attribute.

```php
/**
 * @property int imageable_id
 * @property string imageable_type
 */
class Image extends Model
{
    /**
     * @deprecated
     */
    protected function getBlogPostIdAttribute(): int
    {
        /*
         * We don't want things to fail silently after our migration to
         * a polymorphic relation so we are going to scream loudly instead!
         */
        report(new OutOfBoundsException('The blog_post_id attribute has been removed. Use the imageable_id attribute instead.'));

        return $this->imageable_id;
    }

    /**
     * @deprecated
     */
    protected function setBlogPostIdAttribute(int $value): void
    {
        /*
         * We don't want things to fail silently after our migration to
         * a polymorphic relation so we are going to scream loudly instead!
         */
        report(new OutOfBoundsException('The blog_post_id attribute has been removed. Use the imageable_id attribute instead.'));

        $this->fill([
            'imageable_id' => $value,
            'imageable_type' => BlogPost::class,
        ]);
    }

    // ...
}
```

This accessor and mutator mean that we can still access and set the foreign key like we did before the migration, but it is going to report usages to our error tracker so we can jump in and fix any remaining references to the `Image`'s `blog_post_id` attribute.

Two things to note here:

1. **Reporting an exception**: This will silently report to our error tracking service that we are accessing a deprecated attribute. Doing this will hopefully mean we'll act quickly if we notice any usages. Alternatively you could write to your logs, but unless you are being notified, you might just forget. Screaming loudly and acting swiftly is a better approach in my opinion.
2. **Filling the imageable_type**: We need to ensure that we set the `imageable_id` and the `imageable_type` when we are intercepting calls to `$image->blog_post_id = $x`

I'm not 100% sure if [`OutOfBoundsException`](https://www.php.net/manual/en/class.outofboundsexception.php) is the "correct" exception here, but it fits the use case close enough. You could always create your own exception if you wanted.

## A note on mass assignment

If your application is using mass assignment protection, which I would generally advise against (I'll let [Mohamed explain why](https://youtu.be/XNy0hldKHjM)), then you also need to take that into account with this migration. Until you are satisfied that you have removed all mass assignments that include the `blog_post_id` attribute, you should keep it in place.

```php
/**
 * @property int imageable_id
 * @property string imageable_type
 */
class Image extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        'imageable_id',
        'imageable_type',
        /** @deprecated */
        'blog_post_id',
    ];

    // ...
}
```

If we remove the `blog_post_id` from the `$fillable` array, Laravel will silently filter `blog_post_id` out of the incoming attribute payload. This will likely throw an exception because the `imageable_id` is non-nullable in the database. Leaving it there with all the previous steps also taken into account means this will continue to work as expected.

Using `@deprecated` in the `$fillable` array doesn't do anything (and might not even be valid), but it does signal to developers when we look here that we shouldn't be using it.

Once you have had this in production for as long as you feel is necessary, you can then go back through and remove all the deprecated bits and pieces.

That is all there is to do. The great thing about this approach is it means the user is never impacted by the migration. If we do miss some references, it isn't going to blow up on the end user. The application will continue to work as expected, while behind the scenes we find and fix any remaining references to our old foreign key.
