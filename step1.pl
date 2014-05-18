%% Grammar
%% <blck> ::= begin <stmts> end
%% 
%% <stmts> ::= <empty>
%%          | <stmt> <stmts>
%% 
%% <stmt> ::= pass
%%          | declare <name>
%%          | use <name>
%%          | <blck>

%% Statement
stmt([pass|More], More).
stmt([declare,X|More], More) :- not(nonTerminal(X)).
stmt([use,X|More], More) :-  not(nonTerminal(X)).
stmt(Block, More) :- blck(Block, More).
stmt(A) :- stmt(A, []).

%% Statements
stmts([pass|More], More).
stmts([pass|More], Other) :- stmts(More,Other).
stmts([Keyword, Var|More], More) :- stmt([Keyword,Var]).
stmts([Keyword, Var|More], Other) :- stmt([Keyword,Var]), stmts(More,Other).
stmts([begin|Stmts], More) :- stmts(Stmts, [end|More]).
stmts(A) :- stmts(A, []).

%% blck([begin,declare,x,begin,use,x,declare,y,end,use,y,end]).
%% Block
blck([begin,end|Tail], Tail).
blck([begin|Stmts], Tail) :- stmts(Stmts, [end|Tail]).
blck(Block) :- blck(Block, []).

%% Legal
legal(L) :- blck(L,[]).

%% Facts
nonTerminal(pass).
nonTerminal(declare).
nonTerminal(use).
nonTerminal(begin).
nonTerminal(end).
