# Modified Borda Count

Modified Borda Count to combine individual voters' individual preference lists

You can find more details about the algorithm [check this paper](https://arxiv.org/ftp/arxiv/papers/0906/0906.1943.pdf) and the following videos:

- [Telescope Time without Tears - Numberphile](https://www.youtube.com/watch?v=7c0CoXFApnM)
- [Telescope Time (extra footage) - Deep Sky Videos](https://www.youtube.com/watch?v=bplncn4xC74)

## Requirements

- PHP 5.6+

## Installing

Use Composer to install it:

```
composer require filippo-toso/modified-borda-count
```

## Using It

```
use FilippoToso\ModifiedBordaCount\Applications;
use FilippoToso\ModifiedBordaCount\Application;
use FilippoToso\ModifiedBordaCount\Simulator;

// Example with the simulator
$simulator = new Simulator();

$data = $simulator->generate($load = 8, $applicants = 100, $competent = 85, $incompetent = 10, $evil = 5, $perfect = 0);

$simulator->display($data);
```
