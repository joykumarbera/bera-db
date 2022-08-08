
# Bera Db

A simple mysql database warpper for php



## Badges

[![MIT License](https://img.shields.io/apm/l/atomic-design-ui.svg?)](https://github.com/tterb/atomic-design-ui/blob/master/LICENSEs)



## Authors

- [@joykumarbera](https://www.github.com/joykumarbera)

## Features

- Simple Interface
- Add, Edit, Delete, Query method
- Database transactions

## Installation

Install by using composer

```bash
  composer install bera-db
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
#### Run q query

```php
 $db->query($sql, $params = [])
```
#### Get total nunmber of rows

```php
 $db->getNumRows()
```
#### Get single record as an array

```php
 $db->query($sql, $params = [])->one()
```
#### Get single record as an object

```php
 $db->query($sql, $params = [])->oneAsObject()
```
#### Get all records

```php
 $db->query($sql, $params = [])->all()
```
#### Begin a db transaction

```php
 $db->start_transaction()
```
#### End a db transaction

```php
 $db->end_transaction()
```