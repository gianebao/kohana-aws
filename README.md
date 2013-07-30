# AWS Kohana Module [for Kohana 3.3]

Based on [Amazon Web Services PHP SDK 2][sdk-website] as a Kohana module. All examples are carried over.

## Quick Example

### Upload a File to Amazon S3

```
<?php

// You can also specify the configuration group on the first parameter of the factory.
$s3 = AWS::factory()->get('s3');

// Upload a publicly accessible file. The file size, file type, and MD5 hash are automatically calculated by the SDK
try
{
    $s3->putObject(array(
        'Bucket' => 'my-bucket',
        'Key'    => 'my-object',
        'Body'   => fopen('/path/to/file', 'r'),
        'ACL'    => CannedAcl::PUBLIC_READ
    ));
}
catch (S3Exception $e)
{
    echo "There was an error uploading the file.\n";
}
```

## Environment Variables

You can set the `default` connection credentials by setting the value of `AWS_CONFIG` which can contain the path to the configuration file.

[sdk-website]: http://aws.amazon.com/sdkforphp