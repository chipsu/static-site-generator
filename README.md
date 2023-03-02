# Very simple static site generator

Generate static HTML from Markdown and PHP templates.

## Structure
See example folder

## Generate
```
php build.php <src> <dst>
```

## Docker
```
docker build -t static-site-generator .
docker run --rm -it -v $PWD/example:/app/pages:ro -v $PWD/build:/app/build static-site-generator
```

## TODO
- Fix default templates
- Image optimization
