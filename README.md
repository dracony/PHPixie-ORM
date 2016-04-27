# ORM

PHPixie ORM library

[![Build Status](https://travis-ci.org/PHPixie/ORM.svg?branch=master)](https://travis-ci.org/PHPixie/ORM)
[![Test Coverage](https://codeclimate.com/github/PHPixie/ORM/badges/coverage.svg)](https://codeclimate.com/github/PHPixie/ORM)
[![Code Climate](https://codeclimate.com/github/PHPixie/ORM/badges/gpa.svg)](https://codeclimate.com/github/PHPixie/ORM)
[![HHVM Status](https://img.shields.io/hhvm/phpixie/orm.svg?style=flat-square)](http://hhvm.h4cc.de/package/phpixie/orm)

[![Author](http://img.shields.io/badge/author-@dracony-blue.svg?style=flat-square)](https://twitter.com/dracony)
[![Source Code](http://img.shields.io/badge/source-phpixie/orm-blue.svg?style=flat-square)](https://github.com/phpixie/orm)
[![Software License](https://img.shields.io/badge/license-BSD-brightgreen.svg?style=flat-square)](https://github.com/phpixie/orm/blob/master/LICENSE)
[![Total Downloads](https://img.shields.io/packagist/dt/phpixie/orm.svg?style=flat-square)](https://packagist.org/packages/phpixie/orm)

- [ORM](#orm)
    - [Initializing](#initializing)
    - [Models](#models)
        - [Configuration](#configuration)
        - [Entities](#entities)
        - [Queries](#queries)
        - [Extending Models](#extending-models)
    - [Relationships](#relationships)
        - [Querying relationships](#querying-relationships)
        - [One To Many](#one-to-many)
        - [One to One](#one-to-one)
        - [Many To Many](#many-to-many)
    - [Embedded Models in MongoDB](#embedded-models-in-mongodb)
        - [Embeds One](#embeds-one)
        - [Embeds Many](#embeds-many)


## Initializing

```php

$slice = new \PHPixie\Slice();
$database = new \PHPixie\Database($slice->arrayData(array(
    'default' => array(
        'driver' => 'pdo',
        'connection' => 'sqlite::memory:'
    )
)));
$orm = new \PHPixie\ORM($database, $slice->arrayData(array(
    // We will add some configuration later
)));
```

> If you are using the PHPixie Framework the ORM component is already set up for you.
> It is accessable via `$frameworkBuilder->components()->orm()` and can be confgigured
> in the `config/orm.php` file of your bundle.

## Models

A Model consists of a Repository, Queries and Entities and will usuaully map to a table in a relational database
or a document collection in MongoDB.

* Entity - a single item, stored in the database, e.g. an article
* Repository - is used to create and save Entities
* Query - is used to select, update or delete Entities

You can start using the models right away without any configuration, which will use defaults based on the name of the model.

```php
$repository = $orm->repository('article');

$newArticle   = $repository->createEntity();
$articleQuery = $repository->query();

// shorthands
$newArtcile   = $orm->createEntity('article');
$articleQuery = $orm->query('article');
```

### Configuration 

By default ORM assumes that the table name is the plural of the name of the model, and that the name of the primary key is 'id'.
For MongoDB database the default id field '_id' is assumed. You can ovveride these settings for a particular model in your
configuration file:

```php
return array(
    'models' => array(
        'article' => array(
            'type'       => 'database',
            'connection' => 'default',
            'idField'    => 'id'
        ),
        
        // you can also define embedded models
        // if you are using MongoDB,
        // more on that later
    )
);
```

### Entities

```php
// Saving an entity
$article->title = 'Welcome';
$article->save();

// Getting a field with a default value
$article->getField('title', 'No Title');

// Getting a required field
// Will throw an Exception if it is not set
$article->getRequiredField('title');

// Convert to a simple PHP object
// Usefull for serializing
$object = $article->asObject();

// Deleting
$article->delete();
```

### Queries

ORM queries share a lot of syntax with the Database queries, here are a few query examples:

```php
// Find article by name
$article = $orm->query('article')
    ->where('title', 'Welcome')
    ->findOne();

// Find by id
$article = $orm->query('article')
    ->in($id)
    ->findOne();

// Query by multiple ids
$articles = $orm->query('article')
    ->in($ids)
    ->findOne();

// Actually the in() method can be used
// to also include subqueries and entities.
//
// This will select artciles with id '1',
// or the id same as $someArticle, and
// anything matched by $subQuery
$articles = $orm->query('article')
    ->in(array(
        1, $someArticle, $subQuery
    ));

// Multiple conditions
$articles = $orm->query('article')
    ->where('viewsTotal', '>', 2)
    ->or('viewsDone', '<', 5)
    ->find();

// Ordering
// Supports multiple fields
$articles
    ->orderAscendingBy('name')
    ->orderDescendingBy('id');

// Limit and offset
$articles
    ->limit(1)
    ->offset(2);

// It continues in the same way
// as in the Database component

$articles = $orm->query('article')
    ->where('name', 'Welcome')
    ->or(function($query) {
        $querty
            ->where('viewsTotal', '>', 2)
            ->or('viewsDone', '<', 5);
    })
    ->find();

// Alternative syntax for
// nested conditions
$articles = $orm->query('article')
    ->where('name', 'Welcome')
    ->startWhereConditionGroup('or')
        ->where('viewsTotal', '>', 2)
        ->or('viewsDone', '<', 5)
    ->endGroup()
    ->find();
```

If `findOne` is used, a query will return either a single item or `null`.
When using `find` a Loader is returned which can be used as follows:

```php
// Iterate over it
// Note: this can be done only once
foreach($articles as $article) {
    // ...
}

// Convert into an array
// to allow multiple iterations
$articles = $articles->asArray();

// Convert it into plain objects,
// useful for serializing
$data = $articles->asArray(true);
```

If you are going to access a particular relationship for multiple items that you are selecting
you should preload their relationships to avoid multiple datbase queries. This is called *eager loading*.

```php
// To load relationships eagerly
// just pass their names to find() or findOne()
$articles = $query->find(array(
    'author',
    'tags'
));

// You can also preload nested relationships
// using the dot notation
$articles = $query->find(array(
    'author.friends',
));
```

Queries can also be used for updating and deleting items:

```php
// Count matched items
$count = $articleQuery->count();

// Delete matched items
$articleQuery->delete();

// Update a field in all matched items
$articleQuery
    ->update(array(
        'status' => 'published'
    ));

// Some more advanced updating
$articleQuery
    ->getUpdateBuilder()
        ->increment('views', 1)
        ->set('status', 'published')
        // Removing a field in MongoDB
        ->remove('isDraft')
        ->execute();
```

### Extending Models

Extending ORM Models usually forces the developer into coupling business logic to the database,
which makes them hard to test and and debug. In these cases performing any kind of testing requires
inserting some dummy test data into the database. PHPixie ORM solves this problem by allowing Decorator-like
behavior with Wrappers. Instead of extending a class you provide wrappers that might be used to wrap ORM
Entities, Queries and Repositories and add functionality to them.

```php
class UserEntity extends \PHPixie\ORM\Wrappers\Type\Database\Entity
{
    // Get users full name
    public function fullName()
    {
        // You can access the actual entity
        // Using $this->entity;
        return $this->entity->firstName.' '.$this->entity->lastName;
    }
}

class UserQuery extends \PHPixie\ORM\Wrappers\Type\Database\Query
{
    // Extending queries is useful
    // For adding bulk conditions
    public function popular()
    {
        // Access query with $this->query
        $this->query
            ->where('viewsPerDay', '>', 5000)
            ->orWhere('friendCount' '>=', 100);
    }
}

// You will rarely need to extend repositories
class UserRepository extends \PHPixie\ORM\Wrappers\Type\Database\Repository
{
    // Overriding a save method
    // can be used for validation
    public function save($entity)
    {
        if($entity->getField('name') === null) {
            throw new \Exception("You must provide a user name");
        }
        
        $this->repository->save($entity);
    }
}
```

Now we have to register these classes with the ORM.

```php
class ORMWrappers extends \PHPixie\ORM\Wrappers\Implementation
{
    // Model names of database entities to wrap
    protected $databaseEntities = array(
        'user'
    );
    
    // Model names of queries to wrap
    protected $databaseQueries = array(
        'user'
    );
    
    // Model names of repositories to wrap
    protected $databaseQueries = array(
        'user'
    );
    
    // Model names of embedded entities to wrap
    // We cover them later in this manual
    protected $embeddedEntities = array(
        'post'
    );
    
    // Provide methods to build the wrappers
    
    public function userEntity($entity)
    {
        return new UserEntity($entity);
    }
    
    public function userQuery($query)
    {
        return new UserQuery($query);
    }
    
    public function userRepository($repository)
    {
        return new UserRepository($repository);
    }
    
    public function postEntity($entity)
        return new PostEntity($entity);
    }
}
```

Pass an instance of this class when creating the ORM instance:

```php
$wrappers = new ORMWrappers();
$orm = new \PHPixie\ORM($database, $slice->arrayData(array(
    // Configuration options
)), $wrappers);
```

> When using the PHPixie Framework, you already have the `ORMWrappers`
> class already registered and present in your bundle.

## Relationships

PHPixie supports one-to-many, one-to-one, many-to-many relationships,
as well as embeds-one and embeds-many for embedded models. Each relationship
defines a set of properties that are added to entities and queries
and can be used to access related data. You can configure them using the 
`relationships` key in the configuration file:

```php
return array(
    'models' => array(
        // ...
    ),
    'relationships' => array(
        array(
            'type'  => 'manyToMany',
            'left'  => 'article',
            'right' => 'tag'
        )
    )
);
```

> It is possible to define relationships between tables in same database, different databases
> or even between relational databases and MongoDB

### Querying relationships

Before looking at actual relationship types, let's see how can you define conditions on a relationship:

```php
// Lets say we have a one to many relationship
// between categories and articles

// Finding all categories with
// at lest one article
// 
// Note that you use the property name,
// and not the model name here.
// You can modify property names
// in your config file, more on them later
$categoryQuery->relatedTo('articles');

// or all artciles with a category
$articleQuery->relatedTo('category');

// Use logic operators
// like with where()
$articleQuery->orNotRelatedTo('category');

// Find categories related to
// particular articles
$categoryQuery->relatedTo('articles', $articles);

// $articles can be an id of an article,
// an article entity or an article query,
// or an array of them, e.g.
$categoryQuery->relatedTo('articles', $articleQuery);
$categoryQuery->relatedTo('articles', $someArticle);
$categoryQuery->relatedTo('articles', 4); //id

// Will find all categories related
// to any of the defined articles
$categoryQuery->relatedTo('articles', array(
    4, 3, $articleQuery, $articleEntity
));


// Relationship conditions

// Find categories related to articles
// that have a title 'Welcome'
$categoryQuery->relatedTo('articles', function($query) {
    $query
        ->where('title', 'Welcome');
});

// Or a shorthand
// Yo'll be using this a lot
$categoryQuery->where('articles.title', 'Welcome');

// You can use the '.'
// to go deeper in the relationships

// Find categories that have at least one article
// written by the author 'Dracony'
$categoryQuery->where('articles.author.name', 'Dracony');

// Or combine the verbose approach
// with the shorthand one
$categoryQuery->relatedTo('articles.author', function($query) {
    $query
        ->where('name', 'Dracony');
});
```

### One To Many

This is the most common relationship. A category can own many articles, a topic has manty replies, etc.

```php
// Configuration 
return array(
    'models' => array(
        // ...
    ),
    'relationships' => array(
        //...
        
        array(
            // mandatory options
            'type'  => 'oneToMany',
            'owner' => 'category',
            'items' => 'article',
            
            // The following keys are optional
            // and will fallback to the defaults
            // based on model names
            
            'ownerOptions' => array(
                // the name of the property added to the owner
                // e.g. $category->articles();
                //
                // defaults to the plural of the item model
                'property' => 'articles'
            ),
            
            'itemsOptions' => array(
            
                // the name of the property added to items
                // e.g. $article->category()
                //
                // defaults to the owner model
                'property' => 'category',
                
                // the field that is used to link items
                // defaults to '<property>Id'
                'ownerKey' => 'categoryId',
                
                // The default behavior is to set
                // the field defined in ownerKey to null
                // when the owner gets deleted
                //
                // changed it to 'delete' to delete
                // the articles when their category is deleted
                'onOwnerDelete' => 'update'
            )
        )
    )
);
```

Now that we ave relationship properties defined we may start using them:

```php
// Using with entities

// Getting articles category
// The property names used are
// the ones defined in the config
$category = $article->category();

// Get category articles
$articles = $category->articles();

// Add artcle to category
$category->articles->add($article);

// Remove article from category
$category->articles->remove($article);

// Remove categories from all articles
$category->articles->removeAll();

// Or you can do the same
// from the article side
$article->category->set($category);
$article->category->remove();
```

> Note how using properties instead of conventional methods like `addArticle`, `removeAllArticles` tidies up
> your entities, and doesn't result in a heap of methods added to single class when there are multiple relationships defined

```php
// You can use queries, ids and arrays
// anywhere you can use an entity.
// This allows performing bulk operations faster

// Assign first 5 articles
// to a category
$articleQuery
    ->limit(5)
    ->offset(0);
$category->articles->add($articleQuery);
```

Queries also have properties, just like Entities do. This allows yout to perform more operations with
less calls tot he database.

```php
// Assign articles to a category
$articlesQuery->category->set($category);

// Assign articles to a category with a single
// database call, without the need to select
// rows from database
$categoryQuery->where('title', 'PHP');
$articlesQuery->category->set($category);

// Unset categories for all articles
$orm->query('aricle')
    ->category->remove();
    
// Unset only some ctaegories
$categoryQuery->where('title', 'PHP');
$orm->query('aricle')
    ->category->remove($category);
```

> Using queries instead of iterating over entities provides a huge boost to your performance

```php
// You can also use query properties
// for query building
$categoryQuery = $articleQuery
    ->where('title', 'Welcome')
    ->categories();

// is the same as
$categoryQuery = $orm->query('category')
    ->relatedTo('articles.title', 'Welcome');

// or
$articleQuery->where('title', 'Welcome');
$categoryQuery = $orm->query('category')
    ->relatedTo('articles', $articleQuery);
```

> It may seem like too many options at this point, but you can just stick to whichever syntax you prefer best.
> The generated queries stay exactly the same.

### One to One

One to One relationships are very similar to One To Many. The main difference isthat as soon as you attach
a new item to an owner, the previous item gets detached. A good example of this would be a relationship between
an auction lot and the highest bidder. As soon as we set a new person as the highest bidder the old one is unset.
Another example wold be tasks and workers. Where each task can be performed by a single worker.

```php
// Configuration 
return array(
    'models' => array(
        // ...
    ),
    'relationships' => array(
        //...
        
        array(
            // mandatory options
            'type'  => 'oneToOne',
            'owner' => 'worker',
            'items' => 'task',
            
            // The following keys are optional
            // and will fallback to the defaults
            // based on model names
            
            'ownerOptions' => array(
                // the name of the property added to the owner
                // e.g. $worker->task();
                //
                // Unlike manyToMany this uses
                // a singular case by default
                'property' => 'task'
            ),
            
            // note it's 'itemOptions' here
            // but 'itemsOptions' for One To Many
            'itemOptions' => array(
            
                // the name of the property added to items
                // e.g. $task->worker()
                'property' => 'worker',
                
                // the field that is used to link items
                // defaults to '<property>Id'
                'ownerKey' => 'workerId',
                
                // The default behavior is to set
                // the field defined in ownerKey to null
                // when the owner gets deleted
                //
                // changed it to 'delete' to delete
                // the task when its worker is deleted
                'onOwnerDelete' => 'update'
            )
        )
    )
);
```

```php
// Using with entities

// Getting task worker
$worker = $task->worker();

// Getting worker tasks
$worker = $task->worker();

// assign worker to a task
$task->worker->set($worker);
$worker->task->set($task);

// Unset task
$task->worker->remove();
$work->task->remove();
```

> The interface in oneToOne relationships is the same on both sides and is identical to
> the owner property in oneToMany relationships

### Many To Many

The most common many-to-many relationship is between articles and tags. These relationships require a special pivot
table (or a MongoDB collection) to store the links between items.

```php
// Configuration 
return array(
    'models' => array(
        // ...
    ),
    'relationships' => array(
        //...
        
        array(
            // mandatory options
            'type'  => 'manyToMany',
            'left'  => 'article',
            'right' => 'tag',
            
            // The following keys are optional
            // and will fallback to the defaults
            // based on model names
            
            'leftOptions' => array(
                'property' => 'tags'
            ),
            
            'rightOptions' => array(
                'property' => 'articles'
            ),
            
            // depends on property names
            // defaults to <rightProperty><leftProperty>
            'pivot' => 'articlesTags',

            
            'pivotOptions' => array(
            
                // defaults to the connection
                // of the left model
                'connection' => 'default',
                
                // columns in pivot table
                // default to '<property>Id'
                'leftKey'  => 'articleId',
                'rightKey' => 'tagId',
            )
        )
    )
);
```

Using a many-to-many relationship is simiar to using the owner side of a one-to-many one. 

```php
// Add
$article->tags->add($tag);

// Remove a particular tag
$article->tags->remove($tag);

// Remove all tags from article
$article->tags->removeAll();

// Remove all tags from multiple articles
$orm->query('article')
    ->where('status', 'published')
    ->tags->removeAll();
    
// Construct a tag query from article query
$tagQuery = $orm->query('article')
    ->where('status', 'published')
    ->tags();
    
// Everything else can be used 
// in the same way as categories
// in the one-to-many examples
```

Using queries for bulk operations here makes it possible to assign and remove relationships
with a single database call

```php
// Link multiple articles
// to multiple tags in one go
$articleQuery->tags->add($tagQuery);
```

> A lot of work went into optimizing these query operations, and at the moment no other PHP ORM
> supports editing relationships between queries. Instread of requiring m*n queries to edit
> many-to-many relationships, the query approach can achieve it in one go.

## Embedded Models in MongoDB

MongoDB supports nested documents, which allows using embedded models. E.g. the author of an article:

```js
{
    "title" : "Welcome",
    //...
    
    "author" : {
        "name" : "Dracony,
        //...
    }
}
```

Embedded models consis only of Entities and have neither Repositories nor Queries.
Subdocuments and subarrays are not automatically registered as embedded relationships,
you have to do it inside your configuration:

```php
return array(
    'models' => array(
        
        // Some database models
        'article' => array(),
        'topic'   => array(),

        // Configuring an embedded model
        'author' => array(
            'type' => 'embedded'
        ),
    ),
    
    'relationships' => array(
        array(
            'type'  => 'embedsOne',
            'owner' => 'article',
            'item'  => 'author'
        ),
        
        array(
            'type'  => 'embedsOne',
            'owner' => 'topic',
            'item'  => 'author'
        ),
    )
);
```

There are also some usage differences between embedded and database models:

```php
// Embedded entities cannot be saved on their own.
// Instead you just save the database entity
$article->author()->name = 'Dracony';
$article->save();

// Getting the parent model
// Conditions are specified as usual
$articleQuery
    ->where('author.name', 'Dracony');
    
// To specify a subdocument condition
// use a '>' separator.
//
// This will require the article author
// to have a stats.totalPost field 'Dracony'
$articleQuery
    ->where('author>stats.totalPost', 'Dracony');
```

### Embeds One

This is a relationship with a subdocument like the above article author example.

```php
// Configuration 
return array(
    'models' => array(
        // ...
    ),
    'relationships' => array(
        //...
        
        array(
            // mandatory options
            'type'  => 'embedsOne',
            'owner' => 'article',
            'item'  => 'author',
            
            // The following keys are optional
            // and will fallback to the defaults
            // based on model names
            
            'ownerOptions' => array(
                // the name of the property added to the owner
                // e.g. $article->author();
                //
                // Defaults to item model name
                'property' => 'task',
            ),
            
            'itemOptions' => array(
                
                // Dot separated path
                // to the document within owner
                //
                // Defaults to owner property name
                'path' => 'author'
                
                // You can use nested paths
                // e.g. 'authors.editor'
            )
        )
    )
);
```

> Note how we don't define the property for accessing the article from the author. This is because
> accessing the owner is always done using `$entity->owner()` for embedded entities.

```php
// get author from article
$author = $article->author();

// get author owner
$article = $author->owner();

// remove author
$article->author->remove();

// Set an author
$article->author->set($author);

// Check if an author is set
$article->author->exists();

// Create and set new author
$author = $article->author->create();

// Create with data
$author = $article->author->create($data);
```

### Embeds Many

Defines a relationship with an embedded array of subdocuments. E.g. forum topic replies:

```js
{
    "title"   : "Welcome",
    "replies" : [
        {
            "message" : "Hello",
            "author"  : "Dracony"
        },
        {
            "message" : "World",
            "author"  : "Dracony"
        }
    ]
}
```

```php
// Configuration 
return array(
    'models' => array(
        // ...
    ),
    'relationships' => array(
        //...
        
        array(
            // mandatory options
            'type'   => 'embedsMany',
            'owner'  => 'topic',
            'items'  => 'reply',
            
            // The following keys are optional
            // and will fallback to the defaults
            // based on model names
            
            'ownerOptions' => array(
                // the name of the property added to the owner
                // e.g. $topic->replies();
                //
                // Defaults to the plural 
                // of the item model name
                'property' => 'replies',
            ),
            
            'itemOptions' => array(
                
                // Dot separated path
                // to the document within owner
                //
                // Defaults to owner property name
                'path' => 'replies'
                
                // You can use nested paths
                // e.g. 'content.replies'
            )
        )
    )
);
```

```php
// Get replies iterator
$replies = $topic->replies();

// Get reply count
$topic->replies->count();

// Get reply by offset
$reply = $topic->replies->get(2);

// Create and add a reply
$reply = $topic->replies->create();

// Create reply from data
$reply = $topic->replies->create($data);

//Create reply at offset
$reply = $topic->replies->create($data, 2);

// Add reply
$topic->replies->add($reply);

// Add reply by offset
$topic->replies->add($reply, 2);

// Remove reply
$topic->replies->remove($reply);

// Remove multiple replies
$topic->replies->remove($replies);

// Remove reply by offset
$topic->replies->offsetUnset(2);

// Remove all replies
$topic->replies->removeAll();

// Check if a reply exists
$exists = $topic->replies->offsetExists(2);

// array access
$reply = $topic->replies[1];
$topic->replies[2] = $reply;

$exists = isset($topic->replies[2]);
unset($topic->replies[2]);
```
