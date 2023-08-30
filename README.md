<h1 style="text-align: center !important;">
    <a href="https://codefyphp.com/" target="_blank"><img src="https://downloads.joshuaparker.blog/images/codefyphp.png" width="400" alt="CodefyPHP Logo"></a>
</h1> 

<p style="text-align: center !important">
    <a href="https://codefyphp.com/"><img src="https://img.shields.io/packagist/v/CodefyPHP/codefy?label=CodefyPHP" alt="Latest Stable Version"></a>
    <a href="https://www.php.net/"><img src="https://img.shields.io/badge/PHP-8.2-777BB4.svg?style=flat&logo=php" alt="PHP 8.2"/></a>
    <a href="https://packagist.org/packages/codefyphp/codefy"><img src="https://img.shields.io/packagist/l/codefyphp/codefy" alt="License"></a>
    <a href="https://packagist.org/packages/codefyphp/codefy"><img src="https://img.shields.io/packagist/dt/codefyphp/codefy" alt="Total Downloads"></a>
</p>

---

CodefyPHP is not a full-fledged framework such as the likes of Symfony, Laravel, Codeigniter or CakePHP. Codefy is a simple 
framework providing contracts and abstractions for architecting a Domain Driven project with 
CQRS, Event Sourcing and implementations of [PSR-3](https://www.php-fig.org/psr/psr-3), 
[PSR-6](https://www.php-fig.org/psr/psr-6), [PSR-7](https://www.php-fig.org/psr/psr-7), 
[PSR-11](https://www.php-fig.org/psr/psr-11), [PSR-12](https://www.php-fig.org/psr/psr-12/), 
[PSR-15](https://www.php-fig.org/psr/psr-15), [PSR-16](https://www.php-fig.org/psr/psr-16) 
and [PSR-17](https://www.php-fig.org/psr/psr-17).

The philosophy of Codefy is that code should be systematized, maintainable, and follows OOP. CodefyPHP tries not be 
too opinionated, yet encourages best practices and coding standards by following [Qubus Coding 
Standards](https://github.com/QubusPHP/qubus-coding-standard).

## Requirement
- PHP 8.2+
- Additional constraints based on which components are used.

## 🏆 Features
- [Simple routing engine](https://docs.qubusphp.com/routing/)
- Robust [dependency injector](https://docs.qubusphp.com/injector/dependency-injector/) for bootstrapping
- Adapters for cookies, sessions and cache storage
- Provides a simple hook and event system without affecting core code
- Encourages object-oriented programming
- Multiple PSR implementations

## 📦 Installation

You can use the composer command below to install the library, or by creating a new Codefy project using the
[skeleton](https://github.com/CodefyPHP/skeleton) package.

```bash
composer require codefyphp/codefy
```

## Releases
| Version | Minimum PHP Version | Release Date   | Bug Fixes Until | Security Fixes Until |
|---------|---------------------|----------------|-----------------|----------------------|
| 1       | 8.2                 | September 2023 | July 2024       | March 2025           |
| 2 - LTS | 8.2                 | January 2024   | January 2027    | January 2028         |

## 📘 Documentation
Documentation is still a work in progress. Between the [Qubus Components](https://docs.qubusphp.com/) documentation 
and [CodefyPHP's](https://codefyphp.com/) documentation, that should help you get started. If you have questions or 
need help, feel free to ask for help in the [forums](https://codefyphp.com/community/).

## 🙌 Sponsors
Help sponsor the continued development of CodefyPHP.

## Contributing
Thank you for considering contributing to CodefyPHP! Please read the [Contributing Guide](https://docs.qubusphp.com/contributing/) to learn how you can help.

## Security Vulnerabilities
If you discover a vulnerability in the code, please email [joshua@joshuaparker.dev](mailto:joshua@joshuaparker.dev).

## License
CodefyPHP is opensource software licensed under the [MIT License](https://opensource.org/license/MIT/).