
# Bera Db

A simple mysql database warpper for php



## Badges

[![MIT License](https://img.shields.io/apm/l/atomic-design-ui.svg?)](https://github.com/tterb/atomic-design-ui/blob/master/LICENSEs)



## Authors

- [@joykumarbera](https://www.github.com/joykumarbera)

## Features

- Simple Interface
- Add, Edit, Delete, Fetch query helper methods
- Database transactions

## Installation

Install by using composer

```bash
  composer require bera/bera-db
```
    
## Usage/Examples

Setup a connection

```php
require_once __DIR__  . '/vendor/autoload.php';

use Bera\Db\Db;

try {
    $db = new Db('music_app', 'localhost', 'root', '', null, true);
} catch( Bera\Db\Exceptions\DbErrorException $e  ) {
    echo $e->getMessage();
}
```

Insert data

```php
    $db->insert('songs', [
        'title' => 'A New Songs',
        'author' => 'dev',
        'duration' => 300
    ]);
```

Update data

```php
 $db->update('songs', [
        'title' => 'Another Songs'
    ], ['id' => 1]);
```

Delete data

```php
$db->delete('songs', ['id' => 1]);
```

Select data

```php
$db->query('SELECT * FROM songs')->all()
$db->query('SELECT * FROM songs WHERE id = ?', [1])->one()
$db->findAll('songs');
$db->findOne('songs', ['id' => 1]);
```


## API Reference

#### Connect to database

```php
 $db = new Db('music_app', 'localhost', 'root', '', null, true);
```

#### Set debug mode

```php
 $db->setDebugMode(true)
```

#### Insert data

```php
 $db->insert('table_name', $data = [])
```
#### Update data

```php
 $db->update('table_name', $data = [], $conditions = [])
```
#### Delete data

```php
 $db->delete($table, $conditions=[], $glue = 'AND')
```
#### Delete data using AND as a glue

```php
 $db->deleteUsingAnd($table, $conditions=[])
```
#### Delete data using OR as a glue

```php
 $db->deleteUsingOr($table, $conditions=[])
```
#### Run raw query

```php
 $db->query($sql, $params = [])
```
#### Get total nunmber of affected rows

```php
 $db->getAffectedRows()
```

#### Get last insert id

```php
 $db->lastInsertId()
```

#### Get single record as an array after query

```php
 $db->query($sql, $params = [])->one()
```
#### Get single record as an object after query

```php
 $db->query($sql, $params = [])->oneAsObject()
```
#### Get all records as an array after query

```php
 $db->query($sql, $params = [])->all()

```
#### Get a single record using a table name

```php
 $db->findOne($table, $conditions = [], $glue = 'AND', $as = 'object');
```
#### Get all records using a table name

```php
 $db->findOne($table, $conditions = [], $glue = 'AND');
```
#### Begin a db transaction

```php
 $db->start_transaction()
```
#### End a db transaction

```php
 $db->end_transaction()
```