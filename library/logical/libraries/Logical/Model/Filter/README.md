# Logical\Filter

Filter objects are a way to encapsulate list filtering logic and prevent "Death by default" monster methods in the models.

## How it works

Filters are responsible for appending filter query logic to the JDatabaseQuery object. Each class is responsible for one type of filter.

You can add filters to your model, by overriding the model constructor and adding the object to the CollectionModel::$filters array.

When you call CollectionModel::getList and set the appendFilters flag to true, the FilterInterface::addFilterseach method will be called on each filter in the filters array.
