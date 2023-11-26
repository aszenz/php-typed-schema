# PHP Typed Schema

[![ci](https://github.com/aszenz/php-typed-schema/actions/workflows/ci.yml/badge.svg)](https://github.com/aszenz/php-typed-schema/actions/workflows/ci.yml)
[![codecov](https://codecov.io/gh/aszenz/php-typed-schema/graph/badge.svg?token=O3W4QR98RO)](https://codecov.io/gh/aszenz/php-typed-schema)

PHP Typed Schema is a php library to parse unknown data (often as `mixed` type) into properly typed data

Typed Schema has built-in validators's for common use cases and a simple way to compose them to validate any form of data

## Use cases

### Parsing array into typed dto

Let's say we have a well typed class `Order`

```php
/**
 * @psalm-immutable
 */
final class Order
{
    public function __construct(
        public int $id,
        public float $qty,
        public \DateTimeImmutable $date
    ) {
    }
}
```

And we want to create it's object from a mixed data type like an array safely

```php
// Assume this array comes from some external data source, and hence it's type is mixed.
/** @var mixed **/
$orderInfo = ['id' => 1, 'quantity' => '123.20', 'order_date' => '02-02-2021'];
```

To convert this array to an object we write a schema for the expected array shape so that we can validate it.

The library provides a function `Decoder::arrayKey('foo', Decoder::int())` which defines an array key `foo` of type `int`.

Essentially we want to validate that the array contains three keys of different value type's.

To represent this schema the library provides several functions like `map2`, `map3`, `map4` that compose different decoders.

The library also provides built-in decoders/validators that parse numeric and date strings into their respective types.

Using them we can define our Order object's decoder/validator

```php
// The type of this decoder is Decoder<Order>
$orderDecoder = Decoder::map3(
    Decoder::arrayKey('id', Decoder::int()),
    // Notice how array key/value's don't have to match the object properties
    Decoder::arrayKey('quantity', Decoder::numeric()),
    Decoder::arrayKey('order_date', Decoder::dateString('d-m-Y')),
    fn (int $id, float $qty, \DateTimeImmutable $date) => new Order($id, $qty, $date)
);
```

After defining it we can run it on our array and get the dto or an error if the schema didn't match the data.

Running the decoder gives us a `Result` type which can be either an `Ok` or `Error` object, we can unwrap it (throw's exception in case of error) to get our dto.

```php
// Psalm/phpstan will correctly infer the  result as Order
$dto = $orderDecoder->run($orderInfo)->unwrap();
```

### Parsing `mixed` array's to list of dto's

We can easily convert a list of array's to a list of dto's

Following from our previous example:

```php
/** @var mixed */
$ordersInfo = [
    ['id' => 1, 'quantity' => '123.20', 'order_date' => '02-02-2021'],
    ['id' => 2, 'quantity' => '3.20', 'order_date' => '03-04-2021'],
];
```

To convert this into a list of dto's we can reuse our existing decoder defined above:

```php
// Psalm/phpstan will correctly infer it as list<Order>
$listOfDtos = Decoder::listOf($orderDecoder)->run($ordersInfo)->unwrap();
```

### Parsing json to dto

To validate json safely without any coupling between php objects and json fields, we can define the expected shape of the json and decode it for our use.

```php
$jsonData = <<<JSON
{
    "name": "Foo",
    "age": 21,
    "hobbies": ["writing", "travelling"]
}
JSON;

$decoderForJson = Decoder::jsonDecode(
    Decoder::map3(
        Decoder::arrayKey('name', Decoder::nonEmptyString()),
        Decoder::arrayKey('age', Decoder::positiveInt()),
        Decoder::arrayKey('hobbies', Decoder::nonEmptyListOf(Decoder::string())),
        /** @psalm-pure */
        function (string $name, int $age, array $hobbies) {
            // One can convert these variables into an object or
            // use them directly to perform some transformation
        }
    )
);

$result = $decoderForJson->run($jsonData);

if ($result->isErr()) {
    echo 'Bad json data';
}

$data = $result->unwrap();
```

## Comparison with other php validation libraries

Other libraries in the php ecosystem are focused on validation or hydration of data into objects.

Typed schema separates concerns of parsing data and creating objects.

It leaves creating objects (or typed information) to the user, only performing the necessary checks to validate data conforms to the schema.

This approach is explicit and doesn't couple object's properties/constructors with the data source.

## Credits

- Inspired by [Elm's Json Decode library](https://package.elm-lang.org/packages/elm/json/latest/Json.Decode)

- [Parse don't validate](https://lexi-lambda.github.io/blog/2019/11/05/parse-don-t-validate/)
