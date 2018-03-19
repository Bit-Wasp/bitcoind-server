bitcoind server
================

[![Build Status](https://travis-ci.org/Bit-Wasp/bitcoind-server.svg?branch=master)](https://travis-ci.org/Bit-Wasp/bitcoind-server)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Bit-Wasp/bitcoind-server/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Bit-Wasp/bitcoind-server/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/Bit-Wasp/bitcoind-server/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/Bit-Wasp/bitcoind-server/?branch=master)

This library can be used to creating bitcoin node data
directories with a certain configuration, or to boot
a bitcoind instance against a certain directory.

The NodeService provides a simple API for this function.

The UnitTestNodeService provides an API for creating
once off regtest nodes, whose data directories will be
cleaned up when the service instance is destructed.

The Server class can also be used as a factory for
producing an `nbobtc/bitcoind` RPC client configured
to use the running instance.
