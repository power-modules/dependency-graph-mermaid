.PHONY: test codestyle phpstan devcontainer diagrams generate-diagrams verify-diagrams ci

test:
	vendor/bin/phpunit --color=always --no-coverage test/

codestyle:
	vendor/bin/php-cs-fixer check --config=.php-cs-fixer.php .

phpstan:
	vendor/bin/phpstan analyse --memory-limit=4G --configuration=phpstan.neon --no-progress --no-interaction src/ test/

devcontainer:
	docker build -t power-modules-devcontainer -f DockerfileDevContainer .

# Generate example Mermaid diagrams and validate their basic structure
diagrams: generate-diagrams verify-diagrams
	@echo "Diagrams generated and validated."

generate-diagrams:
	php examples/ecommerce/generate.php

verify-diagrams:
	@test -f examples/ecommerce/mermaid/ecommerce_full.mmd
	@grep -q '^graph LR' examples/ecommerce/mermaid/ecommerce_full.mmd
	@test -f examples/ecommerce/mermaid/ecommerce_class_full.mmd
	@grep -q '^classDiagram' examples/ecommerce/mermaid/ecommerce_class_full.mmd
	@grep -q '^direction TB' examples/ecommerce/mermaid/ecommerce_class_full.mmd
	@test -f examples/ecommerce/mermaid/ecommerce_timeline.mmd
	@grep -q '^timeline' examples/ecommerce/mermaid/ecommerce_timeline.mmd
	@grep -q '^section Infrastructure' examples/ecommerce/mermaid/ecommerce_timeline.mmd
	@grep -q '^section Domain' examples/ecommerce/mermaid/ecommerce_timeline.mmd

# CI target combines static analysis, tests, and example diagram verification
ci: codestyle phpstan test diagrams
	@echo "CI pipeline succeeded."
