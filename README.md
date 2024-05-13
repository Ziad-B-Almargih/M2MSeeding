# M2MSeeding
**this package is useful for seeding fake data between two Models with Many-To-Many relationship by some function**

## Installation 
```
composer require zbm/m2m-seeding
```

## Usage

### Basic usage
```php
use m2m\seeding\M2MSeeding;


M2MSeeding::make(FirstModel::class, SecondModel::class, 'relation')
    ->run();
```
- first thing call the static function ***make()*** with the first and second models you need with the name of relation from first model to second model 
- then call ***run()*** to seeding the fake data

### Factory the models 
**if you want to seed the models using factories you can call ***withFactory()*** function**

```php
use m2m\seeding\M2MSeeding;

M2MSeeding::make(FirstModel::class, SecondModel::class, 'relation')
    ->withFactory(10, 10)
    ->run();
```
the first parameter is the count of factories in the first model and the second parameter is for second one

### Detect number of relations

**you can use this functions to determine number of relations between the models**

```php
->minRelation(10)
```
determine the minimum number of relations (the default value is 0).

```php
->maxRelation(10)
```
determine the maximum number of relations (the default value is 3).

```php
->rangeRelation(2, 5)
```
determine the minimum and maximum number of relations.

### Seed the pivot

**if the pivot have some data you can also seed it by using withPivot() function**

```php
->withPivot(function (){
    return [
        'first_column'  => rand(1, 10),
        'second_column' => fake()->word,
        'third_column'  => true,
    ];
})
```
this function accept callback function returned array of keys (the column name) and values (the value of column)

## Example

### First Model

```php 
class Post extends Model{
    
    public function reactions(){
        return $this->belongsToMany(User::class, 'reactions');
    }
}
```

### Second Model

```php 
class User extends Model{
    
}
```

### reactions table
- **id**
- **user_id**
- **post_id**
- **reaction_type** 

### Seeding
```php
use m2m\seeding\M2MSeeding;

M2MSeeding::make(Post::class, User::class, 'reactions')
    ->withFactory(20, 100)
    ->rangeRelation(50, 80)
    ->withPivot(function (){
        return [
            'reaction_type' => rand(1, 6)
        ];
    })
    ->run();
```
**this lines will create 20 Post and 100 User and each Post has between 50 and 80 reactions 
and each reaction has type as integer between 1 and 6**
