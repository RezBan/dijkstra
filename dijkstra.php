<?php

interface NodeInterface {
	// Соединяет одну $node с другой.
	public function connect(NodeInterface $node, $distance = 1);
	// Возвращает соединения выбоанной ноды.
	public function getConnections();
	// Возвращает идентификатор выбранной ноды
	public function getId();
	// Возвращает потенциал ноды. 
	public function getPotential();
	// Возвращает ноду которая дала выбранной ноде его потенциал.
	public function getPotentialFrom();
	// Возвращает, прошла ли нода или нет.
	public function isPassed();
	// Помечает эту ноду как пройденную, что означает, что для этого графа она уже рассчитала свой потенциал.
	public function markPassed();
	// Устанавливает потенциал для ноды, если нода не имеет потенциала или он выше, чем новый.
	public function setPotential($potential, NodeInterface $from);
}

interface GraphInterface {
	// Добавляет новую ноду к текущему графу.
	public function add(NodeInterface $node);
	// Возвращает ноду связанную с $id этого графа.
	public function getNode($id);
	// Возвращает все ноды принадлижащие этому графу.
	public function getNodes();
}

class Graph implements GraphInterface {
	// Все ноды в графе.
	protected $nodes = array();

	// Добавляет новую ноду в текущий граф
	public function add(NodeInterface $node) {
		if (array_key_exists($node->getId(), $this->getNodes())) {
			throw new Exception('Unable to insert multiple Nodes with the same ID in a Graph');
		}
		$this->nodes[$node->getId()] = $node;
		return $this;
	}

	// Возвращает ноду связанную с $id этого графа.
	public function getNode($id) {
		$nodes = $this->getNodes();
		if (! array_key_exists($id, $nodes)) {
			throw new Exception("Unable to find $id in the Graph");
		}
		return $nodes[$id];
	}

	// Возвращает все ноды принадлижащие этому графу.
	public function getNodes() {
		return $this->nodes;
	}
}

class Node implements NodeInterface {
	protected $id;
	protected $potential;
	protected $potentialFrom;
	protected $connections = array();
	protected $passed = false;

	// Создает новую ноду, запрашивая ID, чтобы избежать пересечений.
	public function __construct($id) {
		$this->id = $id;
	}

	// Соединяет одну ноду с другой.
	public function connect(NodeInterface $node, $distance = 1) {
		$this->connections[$node->getId()] = $distance;
	}

	// Возвращает расстояние до ноды.
	public function getDistance(NodeInterface $node) {
		return $this->connections[$node->getId()];
	}

	// Возвращает соединениия текущей ноды.
	public function getConnections() {
		return $this->connections;
	}

	// Возвращает $id текущей ноды.
	public function getId() {
		return $this->id;
	}

	// Возвращает потенциал ноды.
	public function getPotential() {
		return $this->potential;
	}

	// Возвращает ноду которая дала этот потенциал.
	public function getPotentialFrom() {
		return $this->potentialFrom;
	}

	// Возвращает прошла ли нода или нет.
	public function isPassed() {
		return $this->passed;
	}

	// Помечает эту ноду как пройденную, что означает, что для этого графа она уже рассчитала свой потенциал.
	public function markPassed() {
		$this->passed = true;
	}

	// Устанавливает потенциал для ноды, если нода не имеет потенциала или он выше, чем новый.
	public function setPotential($potential, NodeInterface $from) {
		$potential = ( int ) $potential;
		if (! $this->getPotential() || $potential < $this->getPotential()) {
			$this->potential = $potential;
			$this->potentialFrom = $from;
			return true;
		}
		return false;
	}
}

class Dijkstra {
	protected $startingNode;
	protected $endingNode;
	protected $graph;
	protected $paths = array();
	protected $solution = false;

	// Инициализирует новый алгоритм, требуемый для работы с графом.
	public function __construct(Graph $graph) {
		$this->graph = $graph;
	}

	// Возвращает расстояние между стартовой и конечной точкой.
	public function getDistance() {
		if (! $this->isSolved()) {
			throw new Exception("Cannot calculate the distance of a non-solved algorithm:\nDid you forget to call ->solve()?");
		}
		return $this->getEndingNode()->getPotential();
	}

	// Получает ноду на которую мы указываем.
	public function getEndingNode() {
		return $this->endingNode;
	}

	// Возвращает решение в читаемом виде.
	public function getLiteralShortestPath() {
		$path = $this->solve();
		$literal = '';
		foreach ( $path as $p ) {
			$literal .= "{$p->getId()} - ";
		}
		return substr($literal, 0, count($literal) - 4);
	}

	// Вычисляет кратчайший путь графика благодаря с помощью потенциала в нодах.
	public function getShortestPath() {
		$path = array();
		$node = $this->getEndingNode();
		while ( $node->getId() != $this->getStartingNode()->getId() ) {
			$path[] = $node;
			$node = $node->getPotentialFrom();
		}
		$path[] = $this->getStartingNode();
		return array_reverse($path);
	}

	// Извлекает ноду, из которой мы начинаем вычислять кратчайший путь.
	public function getStartingNode() {
		return $this->startingNode;
	}

	// Устанавливает ноду, на которую мы указываем.
	public function setEndingNode(Node $node) {
		$this->endingNode = $node;
	}

	// Устанавливает ноду, из которого мы начинаем, чтобы вычислить кратчайший путь.
	public function setStartingNode(Node $node) {
		$this->paths[] = array($node);
		$this->startingNode = $node;
	}

	// Решает алгоритм и возвращает кратчайший путь как массив.
	public function solve() {
		if (! $this->getStartingNode() || ! $this->getEndingNode()) {
			throw new Exception("Cannot solve the algorithm without both starting and ending nodes");
		}
		$this->calculatePotentials($this->getStartingNode());
		$this->solution = $this->getShortestPath();
		return $this->solution;
	}

	// Рекурсивно вычисляет потенциалы графа из начальной точки, которую мы указываем с помощью -> setStartingNode (), пересекая граф из-за атрибута ноды $connections.
	protected function calculatePotentials(Node $node) {
		$connections = $node->getConnections();
		$sorted = array_flip($connections);
		krsort($sorted);
		foreach ( $connections as $id => $distance ) {
			$v = $this->getGraph()->getNode($id);
			$v->setPotential($node->getPotential() + $distance, $node);
			foreach ( $this->getPaths() as $path ) {
				$count = count($path);
				if ($path[$count - 1]->getId() === $node->getId()) {
					$this->paths[] = array_merge($path, array($v));
				}
			}
		}
		$node->markPassed();
		// Получить петлю через ближайшие соединения текущей ноды, для вычисления их потенциалов.
		foreach ( $sorted as $id ) {
			$node = $this->getGraph()->getNode($id);
			if (! $node->isPassed()) {
				$this->calculatePotentials($node);
			}
		}
	}

	// Возвращает граф, связанный с этой частью алгоритма.
	protected function getGraph() {
		return $this->graph;
	}

	// Возвращает возможные пути имеющиесь в графе.
	protected function getPaths() {
		return $this->paths;
	}

	// Проверяет, исправлен ли текущий алгоритм или нет.
	protected function isSolved() {
		return ( bool ) $this->solution;
	}
}

function printShortestPath($from_name, $to_name, $routes) {
	$graph = new Graph();
	foreach ($routes as $route) {
		$from = $route['from'];
		$to = $route['to'];
		$price = $route['price'];
		if (! array_key_exists($from, $graph->getNodes())) {
			$from_node = new Node($from);
			$graph->add($from_node);
		} else {
			$from_node = $graph->getNode($from);
		}
		if (! array_key_exists($to, $graph->getNodes())) {
			$to_node = new Node($to);
			$graph->add($to_node);
		} else {
			$to_node = $graph->getNode($to);
		}
		$from_node->connect($to_node, $price);
	}

	$g = new Dijkstra($graph);
	$start_node = $graph->getNode($from_name);
	$end_node = $graph->getNode($to_name);
	$g->setStartingNode($start_node);
	$g->setEndingNode($end_node);
	echo "From: " . $start_node->getId() . "\n";
	echo "To: " . $end_node->getId() . "\n";
	echo "Route: " . $g->getLiteralShortestPath() . "\n";
	echo "Total: " . $g->getDistance() . "\n";
}

$buffer = file_get_contents('graf.json');
$routes = json_decode($buffer, true);

$from = $_POST['from'];
$to = $_POST['to'];

printShortestPath($from, $to, $routes);