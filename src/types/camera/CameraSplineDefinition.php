<?php

/*
 * This file is part of BedrockProtocol.
 * Copyright (C) 2014-2022 PocketMine Team <https://github.com/pmmp/BedrockProtocol>
 *
 * BedrockProtocol is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol\types\camera;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

final class CameraSplineDefinition{

	/**
	 * @param Vector3[]                      $controlPoints
	 * @param CameraSplineProgressKeyFrame[] $progressKeyFrames
	 * @param CameraSplineRotationKeyFrame[] $rotationKeyFrames
	 * @phpstan-param list<Vector3>                      $controlPoints
	 * @phpstan-param list<CameraSplineProgressKeyFrame> $progressKeyFrames
	 * @phpstan-param list<CameraSplineRotationKeyFrame> $rotationKeyFrames
	 */
	public function __construct(
		private string $name,
		private float $totalTime,
		private string $splineType,
		private array $controlPoints,
		private array $progressKeyFrames,
		private array $rotationKeyFrames,
	){}

	public function getName() : string{ return $this->name; }

	public function getTotalTime() : float{ return $this->totalTime; }

	public function getSplineType() : string{ return $this->splineType; }

	/**
	 * @return Vector3[]
	 * @phpstan-return list<Vector3>
	 */
	public function getControlPoints() : array{ return $this->controlPoints; }

	/**
	 * @return CameraSplineProgressKeyFrame[]
	 * @phpstan-return list<CameraSplineProgressKeyFrame>
	 */
	public function getProgressKeyFrames() : array{ return $this->progressKeyFrames; }

	/**
	 * @return CameraSplineRotationKeyFrame[]
	 * @phpstan-return list<CameraSplineRotationKeyFrame>
	 */
	public function getRotationKeyFrames() : array{ return $this->rotationKeyFrames; }

	public static function read(ByteBufferReader $in) : self{
		$name = CommonTypes::getString($in);
		$totalTime = LE::readFloat($in);
		$splineType = CommonTypes::getString($in);
		$controlPoints = CommonTypes::readList($in, CommonTypes::getVector3(...));
		$progressKeyFrames = CommonTypes::readList($in, CameraSplineProgressKeyFrame::read(...));
		$rotationKeyFrames = CommonTypes::readList($in, CameraSplineRotationKeyFrame::read(...));

		return new self(
			$name,
			$totalTime,
			$splineType,
			$controlPoints,
			$progressKeyFrames,
			$rotationKeyFrames
		);
	}

	public function write(ByteBufferWriter $out) : void{
		CommonTypes::putString($out, $this->name);
		LE::writeFloat($out, $this->totalTime);
		CommonTypes::putString($out, $this->splineType);
		CommonTypes::writeList($out, $this->controlPoints, CommonTypes::putVector3(...));
		CommonTypes::writeList($out, $this->progressKeyFrames, fn(ByteBufferWriter $out, CameraSplineProgressKeyFrame $v) => $v->write($out));
		CommonTypes::writeList($out, $this->rotationKeyFrames, fn(ByteBufferWriter $out, CameraSplineRotationKeyFrame $v) => $v->write($out));
	}
}
