%% Grammar
%% <blck> ::= begin <stmts> end
%% 
%% <stmts> ::= <empty>
          %% | <stmt> <stmts>
%% 
%% <stmt> ::= pass
%%          | declare <name>
%%          | use <name>
%%          | <blck>

%% Statement
stmt([pass|More], More).
stmt([declare,X|More], More) :- not(nonTerminal(X)).
stmt([use,X|More], More) :-  not(nonTerminal(X)).
stmt([begin|Block], More) :- blck([begin|Block], More).
stmt(A) :- stmt(A, []).

%% Statements
stmts([pass|More], More).
stmts([Keyword, Var|More], More) :- stmt([Keyword,Var]).
stmts([pass|More], Other) :- stmts(More,Other).
stmts([Keyword, Var|More], Other) :- stmt([Keyword,Var]), stmts(More,Other).
stmts([begin|Stmts], Tail) :- stmts(Stmts, [end|Tail]).
stmts(A) :- stmts(A, []).

%% Block
%% blck([begin,declare,x,use,x,end,tail],[tail]).
%% blck([begin,declare,x,begin,use,x,end,end,something,else],
        %%[something,else]).
%% blck([begin|Stmts]) :- stmts(Stmts, [end]). %% Works for no tail
blck([begin,end|Tail], Tail).
blck([begin|Stmts], Tail) :- stmts(Stmts, [end|Tail]).
blck(Block) :- blck(Block, []).


%% Facts
nonTerminal(pass).
nonTerminal(declare).
nonTerminal(use).
nonTerminal(begin).
nonTerminal(end).
